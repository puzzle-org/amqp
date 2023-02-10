<?php

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;

class NullBodyTest extends TestCase
{
    public function testNullBody()
    {
        $body = new EmptyBody();
        
        $this->assertSame('', $body->inOriginalFormat());
        $this->assertSame('', $body->asTransported());
        $this->assertEmpty((string) $body);
    }
}
