<?php

namespace Puzzle\AMQP\Workers\Chunks\ChunkAssemblyTrackers;

use Puzzle\AMQP\Workers\Chunks\ChunkAssemblyTracker;
use Puzzle\ValueObjects\Uuid;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;

class MultipleTracker implements ChunkAssemblyTracker
{
    private
        $trackers,
        $trackerInstanciator;

    public function __construct(\Closure $trackerInstanciator)
    {
        $this->trackers = [];
        $this->trackerInstanciator = $trackerInstanciator;
    }

    public function hasBeenStarted(Uuid $uuid)
    {
        $tracker = $this->getTracker($uuid);

        return $tracker->hasBeenStarted($uuid);
    }

    private function getTracker(Uuid $uuid)
    {
        if(! isset($this->trackers[$uuid->value()]))
        {
            $factory = $this->trackerInstanciator;
            $this->trackers[$uuid->value()] = $factory();
        }

        return $this->trackers[$uuid->value()];
    }

    public function start(ChunkedMessageMetadata $metadata)
    {
        $tracker = $this->getTracker($metadata->uuid());

        return $tracker->start($metadata);
    }

    public function markAsReceived(Uuid $uuid, $playhead)
    {
        $tracker = $this->getTracker($uuid);

        return $tracker->markAsReceived($uuid, $playhead);
    }

    public function isAllHasBeenReceived(Uuid $uuid)
    {
        $tracker = $this->getTracker($uuid);

        return $tracker->isAllHasBeenReceived($uuid);
    }
}
