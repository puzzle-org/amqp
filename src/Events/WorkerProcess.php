<?php


namespace Puzzle\AMQP\Events;

use Symfony\Contracts\EventDispatcher\Event;

class WorkerProcess extends Event
{
    public const NAME = "worker.process";
}
