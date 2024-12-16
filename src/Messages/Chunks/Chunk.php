<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Chunks;

final class Chunk
{
    private ChunkMetadata
        $metadata;
    private ChunkedMessageMetadata
        $messageMetadata;
    private string
        $content;

    public function __construct(int $playhead, int $offset, string $content, ChunkedMessageMetadata $messageMetadata)
    {
        $this->metadata = new ChunkMetadata($playhead, $offset, strlen($content));
        $this->messageMetadata = $messageMetadata;
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function size(): int
    {
        return $this->metadata->size();
    }

    public function getHeaders(): array
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
