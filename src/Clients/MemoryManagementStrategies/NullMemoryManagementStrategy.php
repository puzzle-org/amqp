<?php

namespace Puzzle\AMQP\Clients\MemoryManagementStrategies;

use Puzzle\AMQP\Clients\MemoryManagementStrategy;

class NullMemoryManagementStrategy implements MemoryManagementStrategy
{
    public function init()
    {
    }

    public function manage($sentSize)
    {
    }
}
