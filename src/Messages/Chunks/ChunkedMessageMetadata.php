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

    public function __construct(string|Uuid $uuid, $size, $nbChunks, $checksum)
    {
        $this->uuid = $uuid instanceof Uuid ? $uuid:new Uuid($uuid);
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

        return new self(new Uuid($headers['uuid']), $headers['size'], $headers['nbChunks'], $headers['checksum']);
    }

    public function uuid()
    {
        return $this->uuid;
    }

    public function size()
    {
        return $this->size;
    }

    public function nbChunks()
    {
        return $this->nbChunks;
    }

    public function checksum()
    {
        return $this->checksum;
    }

    public function toHeaders()
    {
        return [
            'uuid' => $this->uuid->value(),
            'size' => $this->size,
            'checksum' => $this->checksum,
            'nbChunks' => $this->nbChunks,
        ];
    }
}
