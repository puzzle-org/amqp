<?php


namespace Puzzle\AMQP\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class WorkerProcess extends Event
{
    public const string NAME = "worker.process";
}
