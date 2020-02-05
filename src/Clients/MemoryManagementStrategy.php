<?php

namespace Puzzle\AMQP\Clients;

interface MemoryManagementStrategy
{
    public function init(): void;
    public function manage(int $sentSize): void;
}
