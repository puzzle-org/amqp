<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\Chunks\Chunk;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;
use Puzzle\AMQP\ValueObjects\Uuid;

class StreamedFile implements Body
{
    private string
        $filepath;
    private ?ChunkSize
        $chunkSize;
    private ?ChunkedMessageMetadata
        $metadata;

    public function __construct(string $filepath, ?ChunkSize $chunkSize = null)
    {
        $this->ensureFilepathIsValid($filepath);

        $this->filepath = $filepath;
        $this->chunkSize = $chunkSize;

        $this->metadata = null;

        if($chunkSize instanceof ChunkSize)
        {
            $size = filesize($filepath);
            $nbChunks = (int) ceil($size / $chunkSize->toBytes());

            $this->metadata = new ChunkedMessageMetadata(new Uuid(), $size, $nbChunks, sha1_file($filepath));
        }
    }

    private function ensureFilepathIsValid(string $filepath): void
    {
        if(is_file($filepath) === false || is_readable($filepath) === false)
        {
            throw new \InvalidArgumentException("Cannot read $filepath");
        }
    }

    public function inOriginalFormat(): false|string
    {
        return file_get_contents($this->filepath);
    }

    public function asTransported(): string|\Generator
    {
        if($this->isChunked() === false)
        {
            return $this->inOriginalFormat();
        }

        return $this->asTransportedInChunks();
    }

    private function asTransportedInChunks(): \Generator
    {
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

    public function getContentType(): string
    {
        return ContentType::BINARY;
    }

    public function __toString(): string
    {
        return sprintf(
            '<binary stream of %d bytes>',
            filesize($this->filepath)
        );
    }

    public function isChunked(): bool
    {
        return $this->chunkSize instanceof ChunkSize;
    }
}
