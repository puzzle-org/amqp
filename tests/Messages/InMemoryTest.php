<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\ReadableMessage;

class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRoutingKeyFromHeader()
    {
        $routingKey = 'pony.under.burgers';
        $message = InMemory::build($routingKey);

        $this->assertTrue($message instanceof ReadableMessage);
        $this->assertSame($routingKey, $message->getRoutingKeyFromHeader());
        $this->assertSame($routingKey, $message->getRoutingKey());
    }
}
