<?php

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;

class NullBodyTest extends TestCase
{
    public function testNullBody()
    {
        $body = new NullBody();
        
        $this->assertNull($body->inOriginalFormat());
        $this->assertNull($body->asTransported());
        $this->assertEmpty((string) $body);
    }
}
