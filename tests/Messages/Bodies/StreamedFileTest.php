<?php

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;

class StreamedFileTest extends TestCase
{
    public function testPublish(): void
    {
        $client = new InMemory();

        $message = new Message('key');
        $message->setStreamedFile(__FILE__);

        $result = $client->publish('myEx', $message);

        self::assertTrue($result);
    }

    public function testToString(): void
    {
        $message = new Message('key');
        $message->setStreamedFile(__FILE__);

        self::assertIsString($message->__toString());
    }

    public function testAsTransportedWithoutChunkSize(): void
    {
        $body = new StreamedFile(__FILE__);

        $message = new Message('key');
        $message->setBody($body);

        self::assertSame(
            $body->inOriginalFormat(),
            $message->getBodyInTransportFormat()
        );
    }

    public function testCannotConstruct(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StreamedFile('/does/not/exist');
    }

    public function testAsTransported(): void
    {
        $filepath = __FILE__;
        $size = new ChunkSize('100K');
        $body = new StreamedFile($filepath, $size);

        $parts = ceil(filesize($filepath) / $size->toBytes());

        $streamedParts = [];
        foreach($body->asTransported() as $part)
        {
            $streamedParts[] = $part;
        }

        self::assertCount(
            (int) $parts,
            $streamedParts
        );
    }
}
