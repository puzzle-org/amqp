<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\Chunks\Chunk;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;
use Puzzle\ValueObjects\Uuid;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;

class StreamedFile implements Body
{
    private
        $filepath,
        $chunkSize,
        $metadata;

    public function __construct($filepath, ChunkSize $chunkSize = null)
    {
        $this->ensureFilepathIsValid($filepath);

        $this->filepath = $filepath;
        $this->chunkSize = $chunkSize;

        $size = filesize($filepath);
        $nbChunks = (int) ceil($size / $chunkSize->toBytes());

        $this->metadata = new ChunkedMessageMetadata(new Uuid(), $size, $nbChunks, sha1_file($filepath));
    }

    private function ensureFilepathIsValid($filepath)
    {
        if(is_file($filepath) === false || is_readable($filepath) === false)
        {
            throw new \InvalidArgumentException("Cannot read $filepath");
        }
    }

    public function inOriginalFormat()
    {
        return file_get_contents($this->filepath);
    }

    /**
     * @return \Generator|string
     */
    public function asTransported()
    {
        if($this->isChunked() === false)
        {
            return $this->inOriginalFormat();
        }

        $offset = 0;
        $playhead = 0;

        $stream = fopen($this->filepath, 'r');

        while(! feof($stream))
        {
            $content = fread($stream, $this->chunkSize->toBytes());
            $playhead++;

            $chunk = new Chunk($playhead, $offset, $content, $this->metadata);
            yield $chunk;

            $offset += $chunk->size();
            unset($chunk, $content);
        }

        fclose($stream);
    }

    public function getContentType()
    {
        return ContentType::BINARY;
    }

    public function __toString()
    {
        return sprintf(
            '<binary stream of %d bytes>',
            filesize($this->filepath)
        );
    }

    public function isChunked()
    {
        return $this->chunkSize instanceof ChunkSize;
    }
}
