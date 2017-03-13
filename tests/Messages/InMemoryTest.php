<?php

namespace Puzzle\AMQP\Messages;

class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRoutingKeyFromHeaders()
    {
        $routingKey = 'pony.under.burgers';
        $message = new InMemory($routingKey);
        
        $this->assertNull($message->getRoutingKeyFromHeader());
        
        // Simulate client publishing that add this header
        $message->addHeader('routing_key', $routingKey);
        
        $this->assertSame($routingKey, $message->getRoutingKeyFromHeader());
    }
}
