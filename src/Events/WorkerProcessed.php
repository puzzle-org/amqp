<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class WorkerProcessed extends Event
{
    public const string NAME = "worker.processed";
}
