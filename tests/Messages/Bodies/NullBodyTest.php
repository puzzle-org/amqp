<?php

namespace Puzzle\AMQP\Messages\Bodies;

class NullBodyTest extends \PHPUnit_Framework_TestCase
{
    public function testNullBody()
    {
        $body = new NullBody();
        
        $this->assertNull($body->inOriginalFormat());
        $this->assertNull($body->asTransported());
        $this->assertEmpty((string) $body);
    }
}
