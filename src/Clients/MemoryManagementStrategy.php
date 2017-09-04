<?php

namespace Puzzle\AMQP\Clients;

use Puzzle\AMQP\Messages\Chunks\ChunkedMessage;

interface MemoryManagementStrategy
{
    public function init(ChunkedMessage $message);
    public function manage($iteration);
}
