<?php

namespace Puzzle\AMQP\Workers\Chunks\ChunkAssemblyTrackers;

use Puzzle\AMQP\Workers\Chunks\ChunkAssemblyTracker;
use Puzzle\ValueObjects\Uuid;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;

class SingleTrackingInMemory implements ChunkAssemblyTracker
{
    private
        $uuid,
        $chunks,
        $nbChunksLeft,
        $nbChunks;

    public function __construct()
    {
        $this->uuid = null;
        $this->chunks = null;
        $this->nbChunksLeft = 0;
        $this->nbChunks = 0;
    }

    public function start(ChunkedMessageMetadata $metadata)
    {
        if($this->uuid instanceof Uuid)
        {
            if($this->isAllHasBeenReceived($this->uuid) === false)
            {
                throw new \LogicException(
                    "Another tracking is in progress (" . $this->uuid . "), cannot start tracking " . $metadata->uuid()
                );
            }
        }

        $nbChunks = $metadata->nbChunks();

        $this->uuid = $metadata->uuid();
        $this->nbChunks = $nbChunks;
        $this->nbChunksLeft = $nbChunks;
        $this->chunks = array_fill(0, $nbChunks, false);
    }

    public function hasBeenStarted(Uuid $uuid)
    {
        return $this->uuid instanceof Uuid
            && $this->uuid->equals($uuid)
            && is_array($this->chunks);
    }

    private function ensureHasBeenStarted(Uuid $uuid)
    {
        if(! $this->hasBeenStarted($uuid))
        {
            throw new \LogicException("Chunk assembly tracking has not been initialized ($uuid)");
        }
    }

    private function convertPlayhead($playhead)
    {
        // 1-based --> 0-based
        return $playhead - 1;
    }

    public function markAsReceived(Uuid $uuid, $playhead)
    {
        $this->ensureHasBeenStarted($uuid);

        $playhead = $this->convertPlayhead($playhead);

        if(! array_key_exists($playhead, $this->chunks))
        {
            throw new \LogicException("Invalid chunk number : $playhead");
        }

        if($this->chunks[$playhead] === false)
        {
            $this->nbChunksLeft--;
        }

        $this->chunks[$playhead] = true;
    }

    public function isAllHasBeenReceived(Uuid $uuid)
    {
        $this->ensureHasBeenStarted($uuid);

        return $this->nbChunksLeft === 0;
    }
}
