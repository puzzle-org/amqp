<?php

namespace Puzzle\AMQP\Clients;

interface MemoryManagementStrategy
{
    public function init();
    public function manage($sentSize);
}
