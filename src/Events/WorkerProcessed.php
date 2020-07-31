<?php


namespace Puzzle\AMQP\Events;

use Symfony\Contracts\EventDispatcher\Event;

class WorkerProcessed extends Event
{
    public const NAME = "worker.processed";
}
