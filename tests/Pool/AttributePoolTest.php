<?php

namespace Hyqo\Http\Test\Pool;

use Hyqo\Http\Pool\AttributePool;
use PHPUnit\Framework\TestCase;

class AttributePoolTest extends TestCase
{
    public function test_get_int()
    {
        $parameterPool = new AttributePool([
            'foo' => 999,
            'bar' => '999',
            'baz' => 'test',
        ]);

        $this->assertEquals(999, $parameterPool->getInt('foo'));
        $this->assertEquals(999, $parameterPool->getInt('bar'));
        $this->assertEquals(0, $parameterPool->getInt('baz'));
    }

    public function test_get_boolean()
    {
        $parameterPool = new AttributePool([
            'foo' => 999,
            'bar' => 'no',
            'baz' => 'yes',
        ]);

        $this->assertFalse($parameterPool->getBoolean('foo'));
        $this->assertFalse($parameterPool->getBoolean('bar'));
        $this->assertTrue($parameterPool->getBoolean('baz'));
    }

}
