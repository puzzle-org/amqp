<?php

namespace Puzzle\AMQP\Messages\Chunks;

use Puzzle\AMQP\Messages\BodyLessMessage;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\ValueObjects\Uuid;

abstract class ChunkedMessage extends BodyLessMessage
{
    protected
        $contentType,
        $metadata,
        $chunkSize;

    public function __construct($routingKey, $size, $checksum, $chunkSize)
    {
        parent::__construct($routingKey);

        $nbChunks = (int) ceil($size / $chunkSize);
        $this->metadata = new ChunkedMessageMetadata(new Uuid(), $size, $nbChunks, $checksum);

        $this->chunkSize = $chunkSize;
        $this->contentType = ContentType::BINARY;
    }

    public function getMetadataHeaders()
    {
        return [
            'uuid' => $this->metadata->uuid()->value(),
            'size' => $this->metadata->size(),
            'checksum' => $this->metadata->checksum(),
            'nbChunks' => $this->metadata->nbChunks(),
        ];
    }

    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    /**
     * @return Generator
     */
    abstract public function getChunkProvider();

    public function getContentType()
    {
        return $this->contentType;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }
}
