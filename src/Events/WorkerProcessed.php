<?php


namespace Puzzle\AMQP\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class WorkerProcessed extends Event
{
    public const NAME = "worker.processed";
}
