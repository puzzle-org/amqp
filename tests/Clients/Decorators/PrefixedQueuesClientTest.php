<?php

namespace Puzzle\AMQP\Clients\Decorators;

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
    /**
     * @dataProvider providerTestGetQueue
     */
    public function testGetQueue($prefix, $queueName, $expected)
    {
        $client = new PrefixedQueuesClient(new MockedClientQueue(), $prefix);

        $this->assertSame($expected, $client->getQueue($queueName));
    }

    public function providerTestGetQueue()
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

    public function testGetExchange()
    {
        $client = new PrefixedQueuesClient(new MockedClientQueue(), 'burger');

        $this->assertSame('poney', $client->getExchange('poney'));
    }

    public function testPublish()
    {
        $mockedClient = new MockedClientQueue();
        $client = new PrefixedQueuesClient($mockedClient, 'pony');

        $message = new Message('routing.key');
        $client->publish('burger', $message);

        $sentMessages = $mockedClient->getSentMessages();
        $this->assertCount(1, $sentMessages);

        $firstMessage = array_shift($sentMessages);

        $this->assertSame($message, $firstMessage['message']);
    }

    public function testGetAppendProcessor()
    {
        $client = new PrefixedQueuesClient(new MockedClientQueue(), 'rainbow');
        $client->appendMessageProcessor(new NullProcessor());
        $this->assertTrue(
            $client->publish('exchange', new Message('null'))
        );
    }

    public function testSetMessageProcessors()
    {
        $client = new PrefixedQueuesClient(new MockedClient(), 'rainbow');
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
