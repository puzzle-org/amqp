<?php

namespace Puzzle\AMQP\Clients\MemoryManagementStrategies;

use Puzzle\AMQP\Clients\MemoryManagementStrategy;

class NullMemoryManagementStrategy implements MemoryManagementStrategy
{
    public function init(): void
    {
    }

    public function manage(int $sentSize): void
    {
    }
}
