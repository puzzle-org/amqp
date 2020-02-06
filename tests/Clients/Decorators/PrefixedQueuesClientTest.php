<?php

namespace Puzzle\AMQP\Clients\Decorators;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Processors\NullProcessor;

class PrefixedQueuesClientTest extends TestCase
{
    /**
     * @dataProvider providerTestGetQueue
     */
    public function testGetQueue(string $prefix, string $queueName, string $expected): void
    {
        $client = new PrefixedQueuesClient(new InMemory(), $prefix);

        $this->assertSame($expected, $client->computePrefixedQueueName($queueName));
    }

    public function providerTestGetQueue(): array
    {
        return [
            'nominal case' => [
                'prefix' => 'burger',
                'queueName' => 'poney',
                'expected' => 'burger' . PrefixedQueuesClient::DELIMITER . 'poney',
            ],
            'empty prefix' => [
                'prefix' => '',
                'queueName' => 'poney',
                'expected' => 'poney',
            ],
            'spaces' => [
                'prefix' => "         \r\n  poney     \t     ",
                'queueName' => 'burger',
                'expected' => 'poney.burger',
            ]
        ];
    }

    public function testPublish(): void
    {
        $mockedClient = new InMemory();
        $client = new PrefixedQueuesClient($mockedClient, 'pony');

        $message = new Message('routing.key');
        $client->publish('burger', $message);

        $sentMessages = $mockedClient->getSentMessages();
        $this->assertCount(1, $sentMessages);

        $firstMessage = array_shift($sentMessages);

        $this->assertSame($message, $firstMessage['message']);
    }

    public function testGetAppendProcessor(): void
    {
        $client = new PrefixedQueuesClient(new InMemory(), 'rainbow');
        $client->appendMessageProcessor(new NullProcessor());
        $this->assertTrue(
            $client->publish('exchange', new Message('null'))
        );
    }

    public function testSetMessageProcessors(): void
    {
        $client = new PrefixedQueuesClient(new InMemory(), 'rainbow');
        $client->setMessageProcessors([
            new NullProcessor(),
            new NullProcessor(),
            new NullProcessor(),
        ]);
        $this->assertTrue(
            $client->publish('exchange', new Message('null'))
        );
    }
}
