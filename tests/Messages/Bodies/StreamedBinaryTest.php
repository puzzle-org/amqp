<?php

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;

class StreamedBinaryTest extends TestCase
{
    public function testAsTransported()
    {
        $body = new StreamedBinary(str_repeat('a', 1000), new ChunkSize(10));

        $asTransported = $body->asTransported();
        $this->assertTrue($asTransported instanceof \Generator);
        $this->assertSame(100, iterator_count($asTransported));
    }
}
