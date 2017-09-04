<?php

namespace Puzzle\AMQP\Workers\Chunks;

use Puzzle\AMQP\Workers\Worker;
use Puzzle\AMQP\ReadableMessage;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;
use Puzzle\AMQP\Messages\Chunks\ChunkMetadata;

class ChunkAssembler implements Worker
{
    use LoggerAwareTrait;

    private
        $tracker,
        $storage;

    public function __construct(ChunkAssemblyTracker $tracker, ChunkAssemblyStorage $storage)
    {
        $this->tracker = $tracker;
        $this->storage = $storage;
        $this->logger = new NullLogger();
    }

    public function process(ReadableMessage $message)
    {
        $headers = $message->getHeaders();

        $chunkMetadata = $this->retrieveChunkMetadata($headers);
        $chunkedMessageMetadata = $this->retrieveChunkedMessageMetadata($headers);
        $uuid = $chunkedMessageMetadata->uuid();

        if($this->tracker->hasBeenStarted($uuid) === false)
        {
            $this->tracker->start($chunkedMessageMetadata);
            $this->storage->start($chunkedMessageMetadata);
        }

        $this->tracker->markAsReceived($uuid, $chunkMetadata->playhead());
        $this->storage->store($uuid, $chunkMetadata, $message->getBodyAsTransported());

        if($this->tracker->isAllHasBeenReceived($uuid))
        {
            var_dump("OK for $uuid");
            // check hash
            $this->storage->finish($chunkedMessageMetadata, $headers);
        }
    }

    private function retrieveChunkedMessageMetadata(array $headers)
    {
        if(! isset($headers['chunkedMessage']))
        {
            throw new \InvalidArgumentException("Missing chunkedMessage header");
        }

        return ChunkedMessageMetadata::buildFromHeaders($headers['chunkedMessage']);
    }

    private function retrieveChunkMetadata(array $headers)
    {
        if(! isset($headers['chunk']))
        {
            throw new \InvalidArgumentException("Missing chunk header");
        }

        return ChunkMetadata::buildFromHeaders($headers['chunk']);
    }
}
