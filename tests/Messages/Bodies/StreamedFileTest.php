<?php

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;

class classTest extends TestCase
{
    public function testPublish()
    {
        $client = new InMemory();

        $message = new Message('key');
        $message->setStreamedFile(__FILE__);

        $result = $client->publish('myEx', $message);

        $this->assertTrue($result);
    }

    public function testToString()
    {
        $message = new Message('key');
        $message->setStreamedFile(__FILE__);

        $this->assertTrue(is_string($message->__toString()));
    }

    public function testAsTransportedWithoutChunkSize()
    {
        $body = new StreamedFile(__FILE__);

        $message = new Message('key');
        $message->setBody($body);

        $this->assertSame(
            $body->inOriginalFormat(),
            $message->getBodyInTransportFormat()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotConstruct()
    {
        new StreamedFile('/does/not/exist');
    }

    public function testAsTransported()
    {
        $filepath = __FILE__;
        $size = new ChunkSize('100K');
        $body = new StreamedFile($filepath, $size);

        $parts = ceil(filesize($filepath) / $size->toBytes());

        $this->assertCount(
            (int) $parts,
            $body->asTransported()
        );
    }
}
