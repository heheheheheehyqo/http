<?php
/** @noinspection ForgottenDebugOutputInspection */

namespace Hyqo\Http\Test;

use PHPUnit\Framework\TestCase;

use function Hyqo\Http\json_response;
use function Hyqo\Http\redirect;
use function Hyqo\Http\text_response;
use function Hyqo\Http\html_response;

class ResponseTest extends TestCase
{
    public function test_send_redirect()
    {
        redirect('/foo')->send();

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
        text_response('foo')->send();
        $content = ob_get_clean();

        $this->assertEquals(['Content-type: text/plain;charset=UTF-8'], xdebug_get_headers());
        $this->assertEquals('foo', $content);
    }

    public function test_send_html()
    {
        ob_start();
        html_response('foo')->send();
        $content = ob_get_clean();

        $this->assertEquals(['Content-type: text/html;charset=UTF-8'], xdebug_get_headers());
        $this->assertEquals('foo', $content);
    }

    public function test_send_json()
    {
        ob_start();
        json_response(['foo'])->send();
        $content = ob_get_clean();

        $this->assertEquals(['Content-Type: application/json'], xdebug_get_headers());
        $this->assertEquals('["foo"]', $content);
    }

    public function test_send_attachment()
    {
        ob_start();
        json_response([])->sendAsAttachment('foo.json', 'application/json');
        $content = ob_get_clean();

        $this->assertEquals([
            'Content-Type: application/json',
            'Content-Disposition: attachment; filename="foo.json"',
            'Content-Length: 2',
        ], xdebug_get_headers());
        $this->assertEquals('[]', $content);
    }
}
