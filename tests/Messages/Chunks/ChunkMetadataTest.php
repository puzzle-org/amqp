<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Chunks;

use PHPUnit\Framework\TestCase;

class ChunkMetadataTest extends TestCase
{
    public function testBuildFromHeaders(): void
    {
        $headers = [
            'playhead' => 2,
            'offset' => 10,
            'size' => 20,
        ];

        $metadata = ChunkMetadata::buildFromHeaders($headers);

        self::assertSame(10, $metadata->offset());
        self::assertSame(20, $metadata->size());
        self::assertSame(2, $metadata->playhead());
    }

    public function testWithInvalidHeaders(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ChunkMetadata::buildFromHeaders([]);
    }
}
