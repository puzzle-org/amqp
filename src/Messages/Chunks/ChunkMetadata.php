<?php

namespace Puzzle\AMQP\Messages\Chunks;

final class ChunkMetadata
{
    private int
        $playhead,
        $offset,
        $size;

    public function __construct(int $playhead, int $offset, int $size)
    {
        $this->playhead = $playhead;
        $this->offset = $offset;
        $this->size = $size;
    }

    public static function buildFromHeaders(array $headers): self
    {
        $requiredKeys = ['playhead', 'offset', 'size'];

        foreach($requiredKeys as $key)
        {
            if(! isset($headers[$key]))
            {
                throw new \InvalidArgumentException("Missing $key in chunk metadata");
            }
        }

        return new self($headers['playhead'], $headers['offset'], $headers['size']);
    }

    public function playhead(): int
    {
        return $this->playhead;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function toHeaders(): array
    {
        return [
            'offset' => $this->offset,
            'playhead' => $this->playhead,
            'size' => $this->size,
        ];
    }
}
