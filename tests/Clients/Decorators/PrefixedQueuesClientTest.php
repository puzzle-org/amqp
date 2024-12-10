<?php

namespace Puzzle\AMQP\Clients\Decorators;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Processors\NullProcessor;

class MockedClientQueue extends InMemory implements Client
{
    public function getQueue($queueName)
    {
        return $queueName;
    }

    public function getExchange($exchangeName)
    {
        return $exchangeName;
    }
}

class PrefixedQueuesClientTest extends TestCase
{
    #[DataProvider('providerTestGetQueue')]
    public function testGetQueue($prefix, $queueName, $expected): void
    {
        $client = new PrefixedQueuesClient(new MockedClientQueue(), $prefix);

        self::assertSame($expected, $client->getQueue($queueName));
    }

    public static function providerTestGetQueue(): array
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

    public function testGetExchange(): void
    {
        $client = new PrefixedQueuesClient(new MockedClientQueue(), 'burger');

        self::assertSame('poney', $client->getExchange('poney'));
    }

    public function testPublish(): void
    {
        $mockedClient = new MockedClientQueue();
        $client = new PrefixedQueuesClient($mockedClient, 'pony');

        $message = new Message('routing.key');
        $client->publish('burger', $message);

        $sentMessages = $mockedClient->getSentMessages();
        self::assertCount(1, $sentMessages);

        $firstMessage = array_shift($sentMessages);

        self::assertSame($message, $firstMessage['message']);
    }

    public function testGetAppendProcessor(): void
    {
        $client = new PrefixedQueuesClient(new MockedClientQueue(), 'rainbow');
        $client->appendMessageProcessor(new NullProcessor());
        self::assertTrue(
            $client->publish('exchange', new Message('null'))
        );
    }

    public function testSetMessageProcessors(): void
    {
        $client = new PrefixedQueuesClient(new MockedClient(), 'rainbow');
        $client->setMessageProcessors([
            new NullProcessor(),
            new NullProcessor(),
            new NullProcessor(),
        ]);
        self::assertTrue(
            $client->publish('exchange', new Message('null'))
        );
    }
}
