<?php

namespace Hyqo\Http\Test;

use Hyqo\Http\Header;
use Hyqo\Http\Method;
use Hyqo\Http\Request;
use Hyqo\Http\TrustedValue;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /**
     * @dataProvider provideBasePathData
     */
    public function test_get_base_path($uri, $server, $expected): void
    {
        $request = Request::create(Method::GET(), $uri, [], [], [], $server);

        $this->assertSame($expected, $request->getBasePath());
    }

    public function provideBasePathData(): array
    {
        return [
            [
                '/web/index.php',
                [
                    'SCRIPT_FILENAME' => '/some/where/index.php',
                    'SCRIPT_NAME' => '/index.php',
                ],
                '/web'
            ],
            [
                '/',
                [
                    'SCRIPT_FILENAME' => '/some/where/index.php',
                ],
                ''
            ],
            [
                '/',
                [
                    'SCRIPT_FILENAME' => '/some/where/index.php',
                    'PHP_SELF' => '/index.php',
                ],
                ''
            ],
            [
                '/we%20b/index.php',
                [
                    'SCRIPT_FILENAME' => '/some/where/index.php',
                    'PHP_SELF' => '/index.php',
                ],
                ''
            ],
        ];
    }

    /**
     * @dataProvider providePathInfoData
     */
    public function test_get_path_info($server, $expected): void
    {
        $request = new Request();
        $request->server->add($server);

        $this->assertSame($expected, $request->getPathInfo());
    }

    public function providePathInfoData(): array
    {
        return [
            [
                [
                    'REQUEST_URI' => '/path/info',
                ],
                '/path/info'
            ],
            [
                [
                    'REQUEST_URI' => '/path%20test/info',
                ],
                '/path%20test/info'
            ],
            [
                [
                    'REQUEST_URI' => '?a=b',
                ],
                '/'
            ],
            [
                [],
                '/'
            ]
        ];
    }

    /**
     * @dataProvider provideBaseUrlData
     */
    public function test_get_base_url($uri, $server, $expectedBaseUrl, $expectedPathInfo): void
    {
        $request = Request::create(Method::GET(), $uri, [], [], [], $server);

        $this->assertSame($expectedBaseUrl, $request->getBaseUrl(), 'baseUrl: ' . $uri);
        $this->assertSame($expectedPathInfo, $request->getPathInfo(), 'pathInfo');
    }

    public function provideBaseUrlData(): array
    {
        return [
            [
                '/fruit/strawberry/1234index.php/blah',
                [
                    'SCRIPT_FILENAME' => 'E:/Sites/cc-new/public_html/fruit/index.php',
                    'SCRIPT_NAME' => '/fruit/index.php',
                    'PHP_SELF' => '/fruit/index.php',
                ],
                '/fruit',
                '/strawberry/1234index.php/blah',
            ],
            [
                '/fruit/strawberry/1234index.php/blah',
                [
                    'SCRIPT_FILENAME' => 'E:/Sites/cc-new/public_html/index.php',
                    'SCRIPT_NAME' => '/index.php',
                    'PHP_SELF' => '/index.php',
                ],
                '',
                '/fruit/strawberry/1234index.php/blah',
            ],
            [
                '/foo%20bar/',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                    'PHP_SELF' => '/foo bar/app.php',
                ],
                '/foo%20bar',
                '/',
            ],
            [
                '/foo%20bar/home',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                    'PHP_SELF' => '/foo bar/app.php',
                ],
                '/foo%20bar',
                '/home',
            ],
            [
                '/foo%20bar/app.php/home',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                    'PHP_SELF' => '/foo bar/app.php',
                ],
                '/foo%20bar/app.php',
                '/home',
            ],
            [
                '/foo%20bar/app.php/home%3Dbaz',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                    'PHP_SELF' => '/foo bar/app.php',
                ],
                '/foo%20bar/app.php',
                '/home%3Dbaz',
            ],
            [
                '/foo/bar+baz',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/app.php',
                    'SCRIPT_NAME' => '/foo/app.php',
                    'PHP_SELF' => '/foo/app.php',
                ],
                '/foo',
                '/bar+baz',
            ],
            [
                '/sub/foo/bar',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/app.php',
                    'SCRIPT_NAME' => '/foo/app.php',
                    'PHP_SELF' => '/foo/app.php',
                ],
                '',
                '/sub/foo/bar',
            ],
            [
                '/sub/foo/app.php/bar',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/app.php',
                    'SCRIPT_NAME' => '/foo/app.php',
                    'PHP_SELF' => '/foo/app.php',
                ],
                '/sub/foo/app.php',
                '/bar',
            ],
            [
                '/sub/foo/bar/baz',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/app2.phpx',
                    'SCRIPT_NAME' => '/foo/app2.phpx',
                    'PHP_SELF' => '/foo/app2.phpx',
                ],
                '',
                '/sub/foo/bar/baz',
            ],
            [
                '/foo/api/bar',
                [
                    'SCRIPT_FILENAME' => '/var/www/api/index.php',
                    'SCRIPT_NAME' => '/api/index.php',
                    'PHP_SELF' => '/api/index.php',
                ],
                '',
                '/foo/api/bar',
            ],
            [
                '/webmaster',
                [
                    'SCRIPT_FILENAME' => '/foo/bar/web/index.php',
                    'SCRIPT_NAME' => '/web/index.php',
                    'PHP_SELF' => '/web/index.php',
                ],
                '',
                '/webmaster',
            ],
        ];
    }

    /**
     * @dataProvider provideRequestUriData
     */
    public function test_get_request_uri($requestUri, $expected, $message): void
    {
        $request = new Request();
        $request->server->add([
            'REQUEST_URI' => $requestUri,
            'SERVER_NAME' => 'test.com',
            'SERVER_PORT' => 80,
        ]);

        $this->assertSame($expected, $request->getRequestUri(), $message);
    }

    public function provideRequestUriData(): \Generator
    {
        $message = 'Do not modify the path.';
        yield ['/foo', '/foo', $message];
        yield ['//bar/foo', '//bar/foo', $message];
        yield ['///bar/foo', '///bar/foo', $message];

        $message = 'Handle when the scheme, host are on REQUEST_URI.';
        yield ['http://test.com/foo?bar=baz', '/foo?bar=baz', $message];

        $message = 'Handle when the scheme, host and port are on REQUEST_URI.';
        yield ['http://test.com:80/foo', '/foo', $message];
        yield ['https://test.com:8080/foo', '/foo', $message];
        yield ['https://test.com:443/foo', '/foo', $message];

        $message = 'Fragment should not be included in the URI';
        yield ['http://test.com/foo#bar', '/foo', $message];
        yield ['/foo#bar', '/foo', $message];
    }

    public function test_is_secure(): void
    {
        $request = Request::create(Method::GET(), 'http://google.com:8080');
        $this->assertFalse($request->isSecure());

        $request = Request::create(Method::GET(), 'https://localhost');
        $this->assertTrue($request->isSecure());
    }

    public function test_is_from_trusted_proxy(): void
    {
        $request = Request::create(Method::GET(), 'localhost');
        $this->assertFalse($request->isFromTrustedProxy());

        Request::setTrustedProxy(['127.0.0.1'], 0);
        $request = Request::create(Method::GET(), 'localhost');
        $this->assertTrue($request->isFromTrustedProxy());
    }

    public function test_get_host(): void
    {
        $request = Request::create(Method::GET(), 'http://google.com:8080');
        $this->assertEquals('google.com', $request->getHost());

        $request = Request::create(Method::GET(), 'https://evil_.com');
        try {
            $request->getHost();
            $this->fail('Should throw an exception');
        } catch (\UnexpectedValueException $exception) {
            $this->assertEquals('Invalid Host "evil_.com".', $exception->getMessage());
        }

        $request = Request::create(Method::GET(), '');
        $request->headers->set(Header::HOST, 'google.com');
        $this->assertEquals('google.com', $request->getHost());
    }

    public function test_very_long_host(): void
    {
        foreach (
            [
                str_repeat('foo.', 90000) . 'bar',
                '[' . str_repeat(':', 90000) . ']'
            ] as $host
        ) {
            $start = microtime(true);

            $request = Request::create(Method::GET(), '/');
            $request->headers->set('host', $host);
            $this->assertEquals($host, $request->getHost());
            $this->assertLessThan(.1, microtime(true) - $start);
        }
    }

    public function test_get_port(): void
    {
        $request = Request::create(Method::GET(), 'http://google.com:8080');
        $this->assertEquals(8080, $request->getPort());

        $request = Request::create(Method::GET(), 'http://localhost');
        $this->assertEquals(80, $request->getPort());

        $request = Request::create(Method::GET(), 'https://localhost');
        $this->assertEquals(443, $request->getPort());

        Request::setTrustedProxy(['127.0.0.1'], TrustedValue::HOST);
        $request = Request::create(Method::GET(), 'https://localhost');
        $request->headers->set(Header::X_FORWARDED_HOST, 'localhost:1234');
        $this->assertEquals(1234, $request->getPort());
    }

    public function test_get_client_ip(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $this->assertEquals('127.0.0.1', $request->getClientIP());

        Request::setTrustedProxy(['127.0.0.1'], 0);
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $request->headers->set(Header::X_FORWARDED_FOR, '::1');
        $this->assertEquals('127.0.0.1', $request->getClientIP());

        Request::setTrustedProxy(['127.0.0.1'], TrustedValue::FOR);
        $request->headers->set(Header::X_FORWARDED_FOR, '::1, ::2');
        $this->assertEquals('::1', $request->getClientIP());
        $this->assertEquals(['::1', '::2'], $request->getClientIPs());
    }

    public function test_get_scheme(): void
    {
        $request = Request::create(Method::GET(), 'https://localhost');
        $this->assertEquals('https', $request->getScheme());

        $request = Request::create(Method::GET(), 'http://localhost');
        $this->assertEquals('http', $request->getScheme());
    }

    public function test_get_parameter(): void
    {
        $request = new Request();
        $request->attributes->set('foo', 'attr');
        $request->query->set('foo', 'query');
        $request->request->set('foo', 'body');

        $this->assertEquals('attr', $request->get('foo'));

        $request->attributes->remove('foo');
        $this->assertEquals('query', $request->get('foo'));

        $request->query->remove('foo');
        $this->assertEquals('body', $request->get('foo'));

        $request->request->remove('foo');
        $this->assertNull($request->get('foo'));
    }
}
