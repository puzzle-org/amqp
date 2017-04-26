<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\Bodies\Text;

class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRoutingKeyFromHeader()
    {
        $content = 'Deuteranope Rex';
        $routingKey = 'pony.under.burgers';
        
        $message = InMemory::build($routingKey, new Text($content));

        $this->assertTrue($message instanceof ReadableMessage);
        $this->assertSame($routingKey, $message->getRoutingKeyFromHeader());
        $this->assertSame($routingKey, $message->getRoutingKey());
        $this->assertSame($content, $message->getBodyInOriginalFormat());
    }
}
