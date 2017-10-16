<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\ValueObjects\Uuid;
use Puzzle\AMQP\Messages\Chunks\Chunk;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;

class StreamedBinary extends Binary
{
    private
        $chunkSize,
        $metadata;

    public function __construct($content, ChunkSize $chunkSize)
    {
        parent::__construct($content);

        $this->chunkSize = $chunkSize;

        $size = strlen($content);
        $nbChunks = (int) ceil($size / $chunkSize->toBytes());

        $this->metadata = new ChunkedMessageMetadata(new Uuid(), $size, $nbChunks, sha1($content));
    }

    public function asTransported()
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

    public function isChunked()
    {
        return true;
    }
}
