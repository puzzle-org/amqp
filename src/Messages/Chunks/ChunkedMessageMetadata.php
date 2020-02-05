<?php

namespace Puzzle\AMQP\Messages\Chunks;

use Puzzle\ValueObjects\Uuid;

final class ChunkedMessageMetadata
{
    private
        $uuid,
        $size,
        $nbChunks,
        $checksum;

    public function __construct(Uuid $uuid, int $size, int $nbChunks, string $checksum)
    {
        $this->uuid = $uuid;
        $this->size = $size;
        $this->nbChunks = $nbChunks;
        $this->checksum = $checksum;
    }

    public static function buildFromHeaders(array $headers)
    {
        $requiredKeys = ['uuid', 'size', 'nbChunks', 'checksum'];

        foreach($requiredKeys as $key)
        {
            if(! isset($headers[$key]))
            {
                throw new \InvalidArgumentException("Missing $key in chunked message metadata");
            }
        }

        return new self(
            new Uuid($headers['uuid']), $headers['size'], $headers['nbChunks'], $headers['checksum']
        );
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function nbChunks(): int
    {
        return $this->nbChunks;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }

    public function toHeaders(): array
    {
        return [
            'uuid' => $this->uuid->value(),
            'size' => $this->size,
            'checksum' => $this->checksum,
            'nbChunks' => $this->nbChunks,
        ];
    }
}
