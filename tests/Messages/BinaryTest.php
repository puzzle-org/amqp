<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\Clients\InMemory;

class BinaryTest extends \PHPUnit_Framework_TestCase
{
    public function testContentType()
    {
        $client = new InMemory();
        
        $message = new Binary('my.routing.key');
        $message->setBody('ABCDEF0123456789');
        
        $client->publish('myExchange', $message);
        
        $sentMessages = $client->getSentMessages();
        $sentMessage = array_shift($sentMessages)['message'];
        
        $this->assertSame(
            ContentType::BINARY,
            $sentMessage->getAttribute('content_type')
        );
        
        $this->assertNotEmpty((string) $sentMessage);
    }
}
