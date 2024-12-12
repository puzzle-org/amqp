<?php


namespace Puzzle\AMQP\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class WorkerRun extends Event
{
    public const string NAME = "worker.run";
}
