<?php

namespace Puzzle\AMQP\Messages\Chunks;

use PHPUnit\Framework\TestCase;

class ChunkMetadataTest extends TestCase
{
    public function testBuildFromHeaders()
    {
        $headers = [
            'playhead' => 2,
            'offset' => 10,
            'size' => 20,
        ];

        $metadata = ChunkMetadata::buildFromHeaders($headers);

        $this->assertSame(10, $metadata->offset());
        $this->assertSame(20, $metadata->size());
        $this->assertSame(2, $metadata->playhead());
    }

    public function testWithInvalidHeaders()
    {
        $this->expectException(\InvalidArgumentException::class);

        ChunkMetadata::buildFromHeaders([]);
    }
}
