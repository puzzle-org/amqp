<?php

namespace Puzzle\AMQP\Clients;

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

    protected function setUp()
    {
        $this->inMemory = new InMemory();
        $this->client = new ChunkedMessageClient($this->inMemory);
    }

    /**
     * @dataProvider providerTestPublish
     */
    public function testPublish($expected, $body)
    {
        $message = new Message('media.test');
        $message->setBody($body);

        $this->client->publish('puzzle', $message);
        $this->assertCountSentMessages($expected);
    }

    public function providerTestPublish()
    {
        $a100KoString = str_repeat("a", 100 * 1024);

        return [
            [100, new StreamedBinary($a100KoString, new ChunkSize('1K'))],
            [10, new StreamedBinary($a100KoString, new ChunkSize('10K'))],
            [1, new Text($a100KoString)],
        ];
    }

    private function assertCountSentMessages($expected)
    {
        $this->assertCount($expected, $this->inMemory->getSentMessages());
    }
}
