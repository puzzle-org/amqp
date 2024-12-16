<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Clients;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Bodies\StreamedBinary;
use Puzzle\AMQP\Messages\Bodies\Text;

class ChunkedMessageClientTest extends TestCase
{
    private
        $inMemory,
        $client;

    protected function setUp(): void
    {
        $this->inMemory = new InMemory();
        $this->client = new ChunkedMessageClient($this->inMemory);
        $this->client->changeRoutingKeyPrefix('partial');
    }

    #[DataProvider('providerTestPublish')]
    public function testPublish($expectedRK, $expectedSize, $body): void
    {
        $message = new Message('media.test');
        $message->setBody($body);

        $this->client->publish('puzzle', $message);
        $this->assertCountSentMessages($expectedSize);
        self::assertSame($expectedRK, $this->retrieveFirstMessage()->getRoutingKey());
    }

    public static function providerTestPublish(): array
    {
        $a100KoString = str_repeat("a", 100 * 1024);

        return [
            ['partial.media.test', 100, new StreamedBinary($a100KoString, new ChunkSize('1K'))],
            ['partial.media.test', 10, new StreamedBinary($a100KoString, new ChunkSize('10K'))],
            ['media.test', 1, new Text($a100KoString)],
        ];
    }

    private function assertCountSentMessages($expected): void
    {
        self::assertCount($expected, $this->inMemory->getSentMessages());
    }

    private function retrieveFirstMessage()
    {
        $messages = $this->inMemory->getSentMessages();

        return reset($messages)['message'];
    }
}
