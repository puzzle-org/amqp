<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\ContentType;

class BinaryTest extends \PHPUnit_Framework_TestCase
{
    public function testContentType()
    {
        $client = new InMemory();
        
        $message = new Message('my.routing.key');
        $message->setBinary('ABCDEF0123456789');
        
        $client->publish('myExchange', $message);
        
        $sentMessages = $client->getSentMessages();
        $sentMessage = array_shift($sentMessages)['message'];
        
        $this->assertSame(
            ContentType::BINARY,
            $sentMessage->getAttribute('content_type')
        );
        
        $this->assertNotEmpty((string) $sentMessage);
    }
    
    public function testGetContentInDifferentFormats()
    {
        $content = decbin(42516982);
        $body = new Binary($content);
        
        $this->assertSame($content, $body->inOriginalFormat());
    }
}
