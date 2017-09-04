<?php

namespace Puzzle\AMQP\Workers\Chunks;

use Puzzle\ValueObjects\Uuid;
use Puzzle\AMQP\Messages\Chunks\ChunkMetadata;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;

interface ChunkAssemblyStorage
{
    public function start(ChunkedMessageMetadata $metadata);
    public function store(Uuid $uuid, ChunkMetadata $metadata, $content);
    public function finish(ChunkedMessageMetadata $metadata, array $headers);
}
