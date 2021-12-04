<?php

namespace Hyqo\HTTP\Test;

use Hyqo\HTTP\Header;
use Hyqo\HTTP\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_send_headers()
    {
        (new Response())->header(Header::LOCATION, 'foo')->send();

        $this->assertEquals(
            [
                'Location: foo'
            ],
            xdebug_get_headers()
        );
    }

    public function test_send_content()
    {
        ob_start();
        (new Response())->send('foo');
        $content = ob_get_clean();

        $this->assertEquals([], xdebug_get_headers());
        $this->assertEquals('foo', $content);
    }

    public function test_send_json()
    {
        ob_start();
        (new Response())->sendJSON(['foo']);
        $content = ob_get_clean();

        $this->assertEquals(['Content-type: application/json'], xdebug_get_headers());
        $this->assertEquals('["foo"]', $content);
    }
}
