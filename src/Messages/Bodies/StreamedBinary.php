<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Chunks\Chunk;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;
use Puzzle\AMQP\ValueObjects\Uuid;

class StreamedBinary extends Binary
{
    private ChunkSize
        $chunkSize;
    private ChunkedMessageMetadata
        $metadata;

    public function __construct($content, ChunkSize $chunkSize)
    {
        parent::__construct($content);

        $this->chunkSize = $chunkSize;

        $size = strlen($content);
        $nbChunks = (int) ceil($size / $chunkSize->toBytes());

        $this->metadata = new ChunkedMessageMetadata(new Uuid(), $size, $nbChunks, sha1($content));
    }

    public function asTransported(): \Generator
    {
        $wholeContent = parent::asTransported();
        $length = strlen($wholeContent);
        $offset = 0;
        $playhead = 0;

        while($offset < $length)
        {
            $content = substr($wholeContent, $offset, $this->chunkSize->toBytes());
            $playhead++;

            $chunk = new Chunk($playhead, $offset, $content, $this->metadata);
            yield $chunk;

            $offset += $chunk->size();
            unset($chunk, $content);
        }
    }

    public function isChunked(): true
    {
        return true;
    }
}
