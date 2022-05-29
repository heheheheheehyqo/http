<?php

namespace Hyqo\Http;

use Hyqo\Http\Pool\ServerPool;

use function Hyqo\String\s;

/**
 * @internal
 */
function extract_prefix(string $requestUri, string $prefix): ?string
{
    if (!has_prefix($requestUri, $prefix)) {
        return null;
    }

    if (preg_match(sprintf('/^(%%[[:xdigit:]]{2}|.){%d}/', \strlen($prefix)), $requestUri, $match)) {
        return $match[0];
    }

    return null;
}

/**
 * @internal
 */
function has_prefix(string $requestUri, string $prefix): bool
{
    return (bool)preg_match(
        sprintf('/^%s(?:$|\/)/', preg_quote($prefix, '/')),
        rawurldecode($requestUri)
    );
}

/**
 * @internal
 */
function contains_script_basename(string $requestUri, string $scriptName): bool
{
    return (bool)preg_match(
        sprintf('/\/%s(?:$|\/)/', preg_quote($scriptName, '/')),
        rawurldecode($requestUri)
    );
}

/**
 * @internal
 */
function fetch_request_uri(ServerPool $server): string
{
    $requestUri = $server->get('REQUEST_URI', '/');
    $requestUri = s($requestUri)->rightCrop('#');

    if (0 === strpos($requestUri, '/')) {
        return $requestUri;
    }

    $components = parse_url($requestUri);

    if (isset($components['path'])) {
        $requestUri = $components['path'];
    } else {
        $requestUri = '';
    }

    if (isset($components['query'])) {
        $requestUri .= '?' . $components['query'];
    }

    if (strpos($requestUri, '/') === false) {
        $requestUri = '/' . $requestUri;
    }

    return $requestUri;
}

function redirect(string $location, ?HttpCode $code = null): Response
{
    return (new Response($code ?? HttpCode::FOUND()))
        ->setHeader(Header::LOCATION, $location);
}

function json_response(array $content, ?HttpCode $code = null): Response
{
    return (new Response($code ?? HttpCode::OK()))
        ->setContentType(ContentType::JSON)
        ->setContent(json_encode($content));
}

function html_response(string $content, ?HttpCode $code = null): Response
{
    return (new Response($code ?? HttpCode::OK()))
        ->setContentType(ContentType::HTML)
        ->setContent($content);
}

function text_response(string $content, ?HttpCode $code = null): Response
{
    return (new Response($code ?? HttpCode::OK()))
        ->setContentType(ContentType::TEXT)
        ->setContent($content);
}
