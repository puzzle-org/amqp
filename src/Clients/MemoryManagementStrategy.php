<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Clients;

interface MemoryManagementStrategy
{
    public function init(): void;
    public function manage(int $sentSize): void;
}
