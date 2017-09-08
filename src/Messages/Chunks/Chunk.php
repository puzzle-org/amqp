<?php

namespace Puzzle\AMQP\Messages\Chunks;

final class Chunk
{
    private
        $metadata,
        $messageMetadata,
        $content;

    public function __construct($playhead, $offset, $content, ChunkedMessageMetadata $messageMetadata)
    {
        $this->metadata = new ChunkMetadata($playhead, $offset, strlen($content));
        $this->messageMetadata = $messageMetadata;
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

    public function getHeaders()
    {
        return [
            'chunk' => $this->metadata->toHeaders(),
            'chunkedMessage' => $this->messageMetadata->toHeaders(),
        ];
    }

    public function __destruct()
    {
        unset($this->content);
    }
}
