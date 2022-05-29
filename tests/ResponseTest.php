<?php
/** @noinspection ForgottenDebugOutputInspection */

namespace Hyqo\Http\Test;

use Hyqo\Http\ContentType;
use Hyqo\Http\Header;
use Hyqo\Http\HttpCode;
use Hyqo\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_set_code()
    {
        $response = (new Response());

        $response->setCode(HttpCode::FORBIDDEN());

        $this->assertEquals(HttpCode::FORBIDDEN, $response->headers->getCode()->value);
    }

    public function test_send_redirect()
    {
        (new Response())
            ->setHeader(Header::LOCATION, '/foo')
            ->send();

        $this->assertEquals(
            [
                'Location: /foo'
            ],
            xdebug_get_headers()
        );
    }

    public function test_send_text()
    {
        ob_start();
        (new Response())
            ->setContentType(ContentType::TEXT)
            ->setContent('foo')
            ->send();
        $content = ob_get_clean();

        $this->assertEquals(['Content-type: text/plain;charset=UTF-8'], xdebug_get_headers());
        $this->assertEquals('foo', $content);
    }

    public function test_send_html()
    {
        ob_start();
        (new Response())
            ->setContentType(ContentType::HTML)
            ->setContent('foo')
            ->send();
        $content = ob_get_clean();

        $this->assertEquals(['Content-type: text/html;charset=UTF-8'], xdebug_get_headers());
        $this->assertEquals('foo', $content);
    }

    public function test_send_json()
    {
        ob_start();

        (new Response())
            ->setContentType(ContentType::JSON)
            ->setContent(json_encode(['foo']))
            ->send();

        $content = ob_get_clean();

        $this->assertEquals(['Content-Type: application/json'], xdebug_get_headers());
        $this->assertEquals('["foo"]', $content);
    }

    public function test_send_attachment()
    {
        ob_start();
        (new Response())
            ->setContentType(ContentType::JSON)
            ->setContent(json_encode(['foo']))
            ->sendAsAttachment('foo.json', 'application/json');

        $content = ob_get_clean();

        $this->assertEquals([
            'Content-Type: application/json',
            'Content-Disposition: attachment; filename="foo.json"',
            'Content-Length: 7',
        ], xdebug_get_headers());
        $this->assertEquals('["foo"]', $content);
    }
}
