<?php

namespace Puzzle\AMQP\Clients\Decorators;

use PHPUnit\Framework\Attributes\DataProvider;
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

    protected function setUp(): void
    {
        $this->memory = new InMemory();
    }

    #[DataProvider('providerTestPublish')]
    public function testPublish($prefix, $expectedExchange): void
    {
        $client = new PrefixedExchangesClient($this->memory, $prefix);
        $client->setLogger(new NullLogger());

        $message = new Message('routing.key');
        $client->publish('unicorn', $message);

        $sentMessages = $this->memory->getSentMessages();
        self::assertCount(1, $sentMessages);

        $firstMessage = array_shift($sentMessages);

        self::assertSame($message, $firstMessage['message']);
        self::assertSame($expectedExchange, $firstMessage['exchange']);
    }

    public static function providerTestPublish(): array
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

    public function testGetQueue(): void
    {
        $this->expectException(\RuntimeException::class);

        $client = new PrefixedExchangesClient($this->memory, 'rainbow');
        $client->getQueue('tail');
    }

    public function testGetExchange(): void
    {
        $client = new PrefixedExchangesClient(new MockedClient(), 'rainbow');
        self::assertSame('rainbow.pizza', $client->getExchange('pizza'));
    }

    public function testGetAppendProcessor(): void
    {
        $client = new PrefixedExchangesClient(new MockedClient(), 'rainbow');
        $client->appendMessageProcessor(new NullProcessor());
        self::assertTrue(
            $client->publish('exchange', new Message('null'))
        );
    }

    public function testSetMessageProcessors(): void
    {
        $client = new PrefixedExchangesClient(new MockedClient(), 'rainbow');
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
