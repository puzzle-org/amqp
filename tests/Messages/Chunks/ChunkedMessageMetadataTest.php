<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Chunks;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\ValueObjects\Uuid;

class ChunkedMessageMetadataTest extends TestCase
{
    public function testConstructWithString(): void
    {
        $metadata = new ChunkedMessageMetadata(
            $uuid = '75f84ada-5df2-46bc-92a2-a6babb7d34e3',
            1024,
            4,
            sha1(str_repeat("a", 1024))
        );

        self::assertSame($uuid, $metadata->uuid());
    }

    public function testConstructWithUuid(): void
    {
        $metadata = new ChunkedMessageMetadata(
            $uuid = new Uuid(),
            1024,
            4,
            sha1(str_repeat("a", 1024))
        );

        self::assertSame($uuid->value(), $metadata->uuid());
    }

    public function testBuildFromHeaders(): void
    {
        $uuid = 'ea62eb94-8be6-4034-9499-9cfc18340eb7';
        $content = str_repeat("a", 1024);

        $headers = [
            'uuid' => $uuid,
            'size' => 1024,
            'nbChunks' => 4,
            'checksum' => sha1($content),
        ];

        $metadata = ChunkedMessageMetadata::buildFromHeaders($headers);

        self::assertSame($uuid, $metadata->uuid());
        self::assertSame(1024, $metadata->size());
        self::assertSame(4, $metadata->nbChunks());
        self::assertSame(sha1($content), $metadata->checksum());
    }

    public function testWithInvalidHeaders(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ChunkedMessageMetadata::buildFromHeaders([]);
    }
}
