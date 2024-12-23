<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Clients;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Processors\NullProcessor;

class InMemoryTest extends TestCase
{
    public function testAll(): void
    {
        $client = new InMemory();
        $client->setMessageProcessors([
            new NullProcessor(),
            new NullProcessor(),
        ]);

        self::assertEmpty($client->getSentMessages());

        $message = new Message('key');
        $client->publish('myEx', $message);

        $sentMessages = $client->getSentMessages();
        self::assertCount(1, $sentMessages);
        foreach($sentMessages as $messageInfo)
        {
            self::assertSame($message, $messageInfo['message']);
        }

        self::assertCount(1, $client->getSentMessages(), 'idempotent');

        $client->dropSentMessages();
        self::assertEmpty($client->getSentMessages());
    }
}
