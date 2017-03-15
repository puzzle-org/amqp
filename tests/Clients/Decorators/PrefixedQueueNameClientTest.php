<?php

use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Clients\Decorators;
use Puzzle\AMQP\Messages;

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

class PrefixedQueueNameClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestGetQueue
     */
    public function testGetQueue($prefix, $queueName, $expected)
    {
        $client = new Decorators\PrefixedQueueNameClient(new MockedClient(), $prefix);

        $this->assertSame($expected, $client->getQueue($queueName));
    }

    public function providerTestGetQueue()
    {
        return [
            'nominal case' => [
                'prefix' => 'burger',
                'queueName' => 'poney',
                'expected' => 'burger' . Decorators\PrefixedQueueNameClient::DELIMITER . 'poney',
            ],
            'empty prefix' => [
                'prefix' => '',
                'queueName' => 'poney',
                'expected' => 'poney',
            ],
        ];
    }

    public function testGetExchange()
    {
        $client = new Decorators\PrefixedQueueNameClient(new MockedClient(), 'burger');

        $this->assertSame('poney', $client->getExchange('poney'));
    }

    public function testPublish()
    {
        $mockedClient = new MockedClient();
        $client = new Decorators\PrefixedQueueNameClient($mockedClient, 'pony');

        $message = new Messages\Json('routing.key');
        $client->publish('burger', $message);

        $sentMessages = $mockedClient->getSentMessages();
        $this->assertCount(1, $sentMessages);

        $firstMessage = array_shift($sentMessages);

        $this->assertSame($message, $firstMessage['message']);
    }
}
