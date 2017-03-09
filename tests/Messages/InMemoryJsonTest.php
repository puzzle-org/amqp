<?php

namespace Puzzle\AMQP\Messages;

class InMemoryJsonTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRoutingKeyFromHeaders()
    {
        $routingKey = 'pony.under.burgers';
        $message = new InMemoryJson($routingKey);
        
        $this->assertNull($message->getRoutingKeyFromHeader());
        
        // Simulate client publishing that add this header
        $message->addHeader('routing_key', $routingKey);
        
        $this->assertSame($routingKey, $message->getRoutingKeyFromHeader());
    }
}
