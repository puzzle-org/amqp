<?php

namespace Puzzle\AMQP\Workers;

use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Consumer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class WorkerContext
{
    use LoggerAwareTrait;

    public
        $queue,
        $description;

    private
        $consumer,
        $worker;

    public function __construct(\Closure $worker, Consumer $consumer, $queue)
    {
        $this->worker = $worker;
        $this->consumer = $consumer;
        $this->queue = $queue;
        $this->logger = new NullLogger();
    }

    public function getWorker()
    {
        if($this->worker instanceof \Closure)
        {
            $closure = $this->worker;
            $this->worker = $closure();
            $this->worker->setLogger($this->logger);
        }

        return $this->worker;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->consumer->setLogger($logger);

        return $this;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getConsumer()
    {
        return $this->consumer;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getQueue()
    {
        return $this->queue;
    }
}
