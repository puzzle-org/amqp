<?php

namespace Puzzle\AMQP\Messages\Chunks;

final class Chunk
{
    private
        $metadata,
        $content;

    public function __construct($playhead, $offset, $content)
    {
        $this->metadata = new ChunkMetadata($playhead, $offset, strlen($content));
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function size()
    {
        return $this->metadata->size();
    }

    public function getMetadataHeaders()
    {
        return [
            'offset' => $this->metadata->offset(),
            'playhead' => $this->metadata->playhead(),
            'size' => $this->metadata->size(),
        ];
    }

    public function __destruct()
    {
        unset($this->content);
    }
}
