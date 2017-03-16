<?php

use Puzzle\AMQP\Client;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Clients\Decorators\PrefixedQueuesClient;
use Puzzle\AMQP\Messages\Message;

class MockedClient extends InMemory implements Client
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

class PrefixedQueuesClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestGetQueue
     */
    public function testGetQueue($prefix, $queueName, $expected)
    {
        $client = new PrefixedQueuesClient(new MockedClient(), $prefix);

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
        $client = new PrefixedQueuesClient(new MockedClient(), 'burger');

        $this->assertSame('poney', $client->getExchange('poney'));
    }

    public function testPublish()
    {
        $mockedClient = new MockedClient();
        $client = new PrefixedQueuesClient($mockedClient, 'pony');

        $message = new Message('routing.key');
        $client->publish('burger', $message);

        $sentMessages = $mockedClient->getSentMessages();
        $this->assertCount(1, $sentMessages);

        $firstMessage = array_shift($sentMessages);

        $this->assertSame($message, $firstMessage['message']);
    }
}
