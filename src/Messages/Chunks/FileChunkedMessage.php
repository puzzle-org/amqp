<?php

namespace Puzzle\AMQP\Messages\Chunks;

class FileChunkedMessage extends ChunkedMessage
{
    private
        $filepath;

    public function __construct($routingKey, $filepath, $chunkSize)
    {
        if(is_file($filepath) === false || is_readable($filepath) === false)
        {
            throw new \InvalidArgumentException("Cannot read $filepath");
        }

        parent::__construct($routingKey, filesize($filepath), sha1_file($filepath), $chunkSize);

        $this->filepath = $filepath;
        $this->addHeaders([
            'file' => [
                'path' => dirname($filepath),
                'filename' => basename($filepath),
        ]]);
    }

    /**
     * @return Generator
     */
    public function getChunkProvider()
    {
        $offset = 0;
        $playhead = 0;

        $stream = fopen($this->filepath, 'r');

        while (! feof($stream))
        {
            $content = fread($stream, $this->chunkSize);
            $playhead++;

            $chunk = new Chunk($playhead, $offset, $content);
            yield $chunk;

            $offset += $chunk->size();
            unset($chunk, $content);
        }

        fclose($stream);
    }
}
