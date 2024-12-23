<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\ContentType;

class BinaryTest extends TestCase
{
    public function testContentType(): void
    {
        $client = new InMemory();
        
        $message = new Message('my.routing.key');
        $message->setBinary('ABCDEF0123456789');
        
        $client->publish('myExchange', $message);
        
        $sentMessages = $client->getSentMessages();
        $sentMessage = array_shift($sentMessages)['message'];
        
        self::assertSame(
            ContentType::BINARY,
            $sentMessage->getAttribute('content_type')
        );
        
        self::assertNotEmpty((string) $sentMessage);
    }
    
    public function testGetContentInDifferentFormats(): void
    {
        $content = decbin(42516982);
        $body = new Binary($content);
        
        self::assertSame($content, $body->inOriginalFormat());
    }
}
