<?php

namespace Hyqo\Http;

use Hyqo\Http\Pool\AttributePool;
use Hyqo\Http\Pool\InputPool;
use Hyqo\Http\Pool\ServerPool;
use Hyqo\Utils\IP;

use function Hyqo\String\s;

class Request
{
    /** @var RequestHeaders */
    public $headers;

    /** @var InputPool */
    public $query;

    /** @var InputPool */
    public $request;

    /** @var AttributePool */
    public $attributes;

    /** @var InputPool */
    public $cookies;

    public $files;

    /** @var ServerPool */
    public $server;

    /** @var string|null */
    protected $content;

    /** @var Method */
    protected $method;

    /** @var string|null */
    protected $host;

    /** @var int */
    protected $port;

    /** @var string */
    protected $baseUrl = null;

    /** @var string */
    protected $basePath = null;

    /** @var string */
    protected $pathInfo = null;

    /** @var string */
    protected $requestUri = null;

    protected static $trustedProxies = [];
    protected static $trustedSet = 0;

    /**
     * @param array $query The GET parameters
     * @param array $request The POST parameters
     * @param array $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array $cookies The COOKIE parameters
     * @param array $files The FILES parameters
     * @param array $server The SERVER parameters
     * @param string|null $content The raw body data
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        ?string $content = null
    ) {
        $this->query = new InputPool($query);
        $this->request = new InputPool($request);
        $this->attributes = new AttributePool($attributes);
        $this->cookies = new InputPool($cookies);
        $this->files = $files;
        $this->server = new ServerPool($server);
        $this->headers = RequestHeaders::createFrom($this->server->all());

        $this->content = $content;
    }

    public static function create(
        Method $method,
        string $url,
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ): Request {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Hyqo/HTTP',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US;q=0.7,en;q=0.3',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ], $server);

        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = $method->value;

        $components = parse_url($url);

        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ($components['scheme'] === 'https') {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] .= ':' . $components['port'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '/';
        }

        switch ($method->value) {
            case Method::POST:
            case Method::PUT:
            case Method::DELETE:
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = ContentType::FORM;
                }
            case Method::PATCH:
                $request = $parameters;
                $query = [];
                break;
            default:
                $request = [];
                $query = $parameters;
                break;
        }

        $queryString = '';
        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $qs);

            if ($query) {
                $query = array_replace($qs, $query);
                $queryString = http_build_query($query, '', '&');
            } else {
                $query = $qs;
                $queryString = $components['query'];
            }
        } elseif ($query) {
            $queryString = http_build_query($query, '', '&');
        }

        $server['REQUEST_URI'] = $components['path'] . ('' !== $queryString ? '?' . $queryString : '');
        $server['QUERY_STRING'] = $queryString;

        return new self($query, $request, [], $cookies, $files, $server, $content);
    }

    public static function createFromGlobals(): self
    {
        $request = new self($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);

        if ($request->getContentType() === ContentType::JSON
            && $request->isMethod(Method::POST)) {
            $data = json_decode($request->getContent(), true) ?? [];
            $request->request = new InputPool($data);
        }

        if ($request->getContentType() === ContentType::FORM
            && $request->isMethod(Method::PUT, Method::DELETE, Method::PATCH)) {
            parse_str($request->getContent(), $data);
            $request->request = new InputPool($data);
        }

        return $request;
    }

    public function isMethod(string ...$methods): bool
    {
        foreach ($methods as $method) {
            if ($this->getMethod()->value === strtoupper($method)) {
                return true;
            }
        }

        return false;
    }

    public function setMethod(Method $method): void
    {
        $this->method = $method;
        $this->server->set('REQUEST_METHOD', $method->value);
    }

    public function getMethod(): Method
    {
        if ($this->method === null) {
            $this->method = Method::from(strtoupper($this->server->get('REQUEST_METHOD', Method::GET)));
        }

        return $this->method;
    }

    public function getContent(): string
    {
        if ($this->content === null) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }

    public function isSecure(): bool
    {
        if ($proto = $this->getTrustedValue(TrustedValue::PROTO)) {
            return $proto === 'https';
        }

        $https = $this->server->get('HTTPS', '');

        return !empty($https) && 'off' !== strtolower($https);
    }

    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function getHost(): string
    {
        if (null === $this->host) {
            $this->host = (function () {
                $host = $this->fetchHost();

                $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

                if ($host && '' !== preg_replace('/[a-z\d\-]+\.?|^\[[:\d]+]$/', '', $host)) {
                    throw new \UnexpectedValueException(sprintf('Invalid Host "%s".', $host));
                }

                return $host;
            })();
        }

        return $this->host;
    }

    protected function fetchHost(): string
    {
        if ($host = $this->getTrustedValue(TrustedValue::HOST)) {
            return $host;
        }

        if ($host = $this->headers->get(Header::HOST)) {
            return $host;
        }

        if ($host = $this->server->get('SERVER_NAME')) {
            return $host;
        }

        if ($host = $this->server->get('SERVER_ADDR')) {
            return $host;
        }

        return '';
    }

    public function getPort(): int
    {
        if (null === $this->port) {
            $this->port = (function (): int {
                if ($port = $this->getTrustedValue(TrustedValue::PORT)) {
                    return (int)$port;
                }

                if (
                    ($host = $this->getTrustedValue(TrustedValue::HOST))
                    || $host = $this->headers->get('Host')
                ) {
                    if ($port = IP::port($host)) {
                        return $port;
                    }

                    return $this->isSecure() ? 443 : 80;
                }

                return (int)$this->server->get('SERVER_PORT');
            })();
        }

        return $this->port;
    }

    public function getHttpHost(): string
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' === $scheme && 80 === $port) || ('https' === $scheme && 443 === $port)) {
            return $this->getHost();
        }

        return $this->getHost() . ':' . $port;
    }

    public function getSchemeAndHttpHost(): string
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }

    public function getUrl(): string
    {
        if (null !== $queryString = $this->getQueryString()) {
            $queryString = '?' . $queryString;
        }

        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $queryString;
    }

    public function getQueryString(): ?string
    {
        if (null === $string = $this->server->get('QUERY_STRING')) {
            return null;
        }

        parse_str($string, $data);

        return http_build_query($data, '', '&', \PHP_QUERY_RFC3986);
    }

    public function generateUrlForPath(string $path): string
    {
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $path;
    }

    public function getScriptName(): string
    {
        return $this->server->get('SCRIPT_NAME', '');
    }

    public function getPathInfo(): string
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = (function (): string {
                if ('/' === ($requestUri = $this->getRequestUri())) {
                    return $requestUri;
                }

                $requestUri = s($requestUri)->rightCrop('?');

                if (!$pathInfo = substr($requestUri, \strlen($this->getBaseUrlReal()))) {
                    return '/';
                }

                return $pathInfo;
            })();
        }

        return $this->pathInfo;
    }

    public function getBasePath(): string
    {
        if (null === $this->basePath) {
            $this->basePath = (function (): string {
                $baseUrl = $this->getBaseUrl();

                if (empty($baseUrl)) {
                    return '';
                }

                $filename = basename($this->server->get('SCRIPT_FILENAME'));
                if (basename($baseUrl) === $filename) {
                    $basePath = \dirname($baseUrl);
                } else {
                    $basePath = $baseUrl;
                }

                if ('\\' === \DIRECTORY_SEPARATOR) {
                    $basePath = str_replace('\\', '/', $basePath);
                }

                return rtrim($basePath, '/');
            })();
        }

        return $this->basePath;
    }

    public function getBaseUrl(): string
    {
        if ($trustedPrefix = $this->getTrustedValue(TrustedValue::PREFIX)) {
            $trustedPrefix = rtrim($trustedPrefix, '/');
        } else {
            $trustedPrefix = '';
        }

        return $trustedPrefix . $this->getBaseUrlReal();
    }

    protected function getBaseUrlReal(): string
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = (function (): string {
                $filename = basename($this->server->get('SCRIPT_FILENAME', ''));

                if ($filename === basename($this->server->get('SCRIPT_NAME', ''))) {
                    $baseUrl = $this->server->get('SCRIPT_NAME', '');
                } else {
                    return '';
                }

                $requestUri = $this->getRequestUri();

                // full $baseUrl matches
                if (null !== $prefix = extract_prefix($requestUri, $baseUrl)) {
                    return $prefix;
                }

                // dirname of $baseUrl matches
                if (null !== $prefix = extract_prefix($requestUri, dirname($baseUrl))) {
                    return $prefix;
                }

                if (!contains_script_basename(explode('?', $requestUri)[0], basename($baseUrl))) {
                    return '';
                }

                // fix for mod_rewrite
                if (($pos = strpos($requestUri, $baseUrl)) && \strlen($requestUri) >= ($baseUrlLen = \strlen(
                        $baseUrl
                    ))) {
                    $baseUrl = substr($requestUri, 0, $pos + $baseUrlLen);
                }

                return $baseUrl;
            })();
        }

        return $this->baseUrl;
    }

    public function getRequestUri(): string
    {
        if (null === $this->requestUri) {
            $this->requestUri = fetch_request_uri($this->server);
        }

        return $this->requestUri;
    }

    public static function setTrustedProxy(array $proxies, int $setBitmask): void
    {
        self::$trustedProxies = $proxies;
        self::$trustedSet = $setBitmask;
    }

    public function isFromTrustedProxy(): bool
    {
        if (!self::$trustedProxies) {
            return false;
        }

        return IP::isMatch($this->server->get('REMOTE_ADDR', ''), self::$trustedProxies);
    }

    public function getTrustedValue(int $bit)
    {
        if (!$this->isFromTrustedProxy()) {
            return null;
        }

        if (!(self::$trustedSet & $bit)) {
            return null;
        }

        switch ($bit) {
            case TrustedValue::FOR:
                return $this->headers->forwarded->getFor();
            case TrustedValue::PROTO:
                return $this->headers->forwarded->getProto();
            case TrustedValue::HOST:
                return $this->headers->forwarded->getHost();
            case TrustedValue::PORT:
                return $this->headers->forwarded->getPort();
            case TrustedValue::PREFIX:
                return $this->headers->forwarded->getPrefix();
            default:
                return null;
        }
    }

    public function getClientIP(): ?string
    {
        return $this->getClientIPs()[0];
    }

    public function getClientIPs(): array
    {
        $ip = $this->server->get('REMOTE_ADDR');

        if ($ips = $this->getTrustedValue(TrustedValue::FOR)) {
            return $ips;
        }

        return [$ip];
    }

    public function get(string $name, $default = null)
    {
        if ($this->attributes->has($name)) {
            return $this->attributes->get($name);
        }

        if ($this->query->has($name)) {
            return $this->query->get($name);
        }

        if ($this->request->has($name)) {
            return $this->request->get($name);
        }

        return $default;
    }

    public function getContentType(): ?string
    {
        return $this->headers->contentType->getMediaType();
    }
}
