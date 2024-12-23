<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;

class StreamedBinaryTest extends TestCase
{
    public function testAsTransported(): void
    {
        $body = new StreamedBinary(str_repeat('a', 1000), new ChunkSize(10));

        $asTransported = $body->asTransported();
        self::assertInstanceOf(\Generator::class, $asTransported);
        self::assertSame(100, iterator_count($asTransported));
    }
}
