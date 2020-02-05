<?php

namespace Puzzle\AMQP\Clients\Decorators;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Clients\InMemory;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Processors\NullProcessor;

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
    public function testPublish(?string $prefix, string $expectedExchange): void
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

    public function providerTestPublish(): array
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
    public function testGetQueue(): void
    {
        $client = new PrefixedExchangesClient($this->memory, 'rainbow');
        $client->getQueue('tail');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetExchange(): void
    {
        $client = new PrefixedExchangesClient(new InMemory(), 'rainbow');
        $this->assertSame('rainbow.pizza', $client->getExchange('pizza'));
    }

    public function testGetAppendProcessor(): void
    {
        $client = new PrefixedExchangesClient(new InMemory(), 'rainbow');
        $client->appendMessageProcessor(new NullProcessor());
        $this->assertTrue(
            $client->publish('exchange', new Message('null'))
        );
    }

    public function testSetMessageProcessors(): void
    {
        $client = new PrefixedExchangesClient(new InMemory(), 'rainbow');
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
