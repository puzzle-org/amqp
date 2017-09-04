<?php

namespace Puzzle\AMQP\Clients\MemoryManagementStrategies;

use Puzzle\AMQP\Clients\MemoryManagementStrategy;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessage;

class NullMemoryManagementStrategy implements MemoryManagementStrategy
{
    public function init(ChunkedMessage $message)
    {
    }

    public function manage($iteration)
    {
    }
}
