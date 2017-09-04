<?php

namespace Puzzle\AMQP\Messages\Chunks;

final class ChunkMetadata
{
    private
        $playhead,
        $offset,
        $size;

    public function __construct($playhead, $offset, $size)
    {
        $this->playhead = $playhead;
        $this->offset = $offset;
        $this->size = $size;
    }

    public static function buildFromHeaders(array $headers)
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

    public function playhead()
    {
        return $this->playhead;
    }

    public function offset()
    {
        return $this->offset;
    }

    public function size()
    {
        return $this->size;
    }
}
