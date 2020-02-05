<?php

namespace Puzzle\AMQP\Clients\Decorators;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Clients\InMemory;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Processors\NullProcessor;

class MockedClient extends InMemory implements Client
{
    public function getExchange($exchangeName)
    {
        return $exchangeName;
    }
}

class PrefixedExchangesClientTest extends TestCase
{
    private
        $memory;

    protected function setUp()
    {
        $this->memory = new InMemory();
    }

    /**
     * @dataProvider providerTestPublish
     */
    public function testPublish($prefix, $expectedExchange)
    {
        $client = new PrefixedExchangesClient($this->memory, $prefix);
        $client->setLogger(new NullLogger());

        $message = new Message('routing.key');
        $client->publish('unicorn', $message);

        $sentMessages = $this->memory->getSentMessages();
        $this->assertCount(1, $sentMessages);

        $firstMessage = array_shift($sentMessages);

        $this->assertSame($message, $firstMessage['message']);
        $this->assertSame($expectedExchange, $firstMessage['exchange']);
    }

    public function providerTestPublish()
    {
        return [
            'nominal' =>
                ['pony', 'pony.unicorn'],
            'left space' =>
                ['       spaceMe', 'spaceMe.unicorn'],
            'left and right space' =>
                ['       space.me    ', 'space.me.unicorn'],
            'multiple keys' =>
                ['burger.fat', 'burger.fat.unicorn'],
            'empty string' =>
                ['', 'unicorn'],
            'null' =>
                [null, 'unicorn'],
        ];
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetQueue()
    {
        $client = new PrefixedExchangesClient($this->memory, 'rainbow');
        $client->getQueue('tail');
    }

    public function testGetExchange()
    {
        $client = new PrefixedExchangesClient(new MockedClient(), 'rainbow');
        $this->assertSame('rainbow.pizza', $client->getExchange('pizza'));
    }

    public function testGetAppendProcessor()
    {
        $client = new PrefixedExchangesClient(new MockedClient(), 'rainbow');
        $client->appendMessageProcessor(new NullProcessor());
        $this->assertTrue(
            $client->publish('exchange', new Message('null'))
        );
    }

    public function testSetMessageProcessors()
    {
        $client = new PrefixedExchangesClient(new MockedClient(), 'rainbow');
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
