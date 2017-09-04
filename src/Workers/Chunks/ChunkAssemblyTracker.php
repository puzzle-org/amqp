<?php

namespace Puzzle\AMQP\Workers\Chunks;

use Puzzle\ValueObjects\Uuid;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;

interface ChunkAssemblyTracker
{
    public function hasBeenStarted(Uuid $uuid);

    public function start(ChunkedMessageMetadata $metadata);

    public function markAsReceived(Uuid $uuid, $playhead);

    public function isAllHasBeenReceived(Uuid $uuid);
}
