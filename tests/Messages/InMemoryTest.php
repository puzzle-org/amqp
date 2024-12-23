<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\Bodies\Text;

class InMemoryTest extends TestCase
{
    public function testGetRoutingKeyFromHeader(): void
    {
        $content = 'Deuteranope Rex';
        $routingKey = 'pony.under.burgers';
        
        $message = InMemory::build($routingKey, new Text($content));

        self::assertInstanceOf(ReadableMessage::class, $message);
        self::assertSame($routingKey, $message->getRoutingKeyFromHeader());
        self::assertSame($routingKey, $message->getRoutingKey());
        self::assertSame($content, $message->getBodyInOriginalFormat());
    }
}
