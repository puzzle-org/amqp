<?php

namespace Puzzle\AMQP\Messages\Chunks;

use Puzzle\AMQP\ValueObjects\Uuid;

final class ChunkedMessageMetadata
{
    private Uuid
        $uuid;
    private int
        $size;
    private int
        $nbChunks;
    private string
        $checksum;

    public function __construct(string|Uuid $uuid, int $size, int $nbChunks, string $checksum)
    {
        if(! $uuid instanceof Uuid)
        {
            $uuid = new Uuid($uuid);
        }

        $this->uuid = $uuid;
        $this->size = $size;
        $this->nbChunks = $nbChunks;
        $this->checksum = $checksum;
    }

    public static function buildFromHeaders(array $headers): self
    {
        $requiredKeys = ['uuid', 'size', 'nbChunks', 'checksum'];

        foreach($requiredKeys as $key)
        {
            if(! isset($headers[$key]))
            {
                throw new \InvalidArgumentException("Missing $key in chunked message metadata");
            }
        }

        return new self($headers['uuid'], (int) $headers['size'], (int) $headers['nbChunks'], $headers['checksum']);
    }

    public function uuid(): string
    {
        return $this->uuid->value();
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
