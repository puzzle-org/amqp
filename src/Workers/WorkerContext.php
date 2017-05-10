<?php

namespace Puzzle\AMQP\Workers;

use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Consumer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class WorkerContext
{
    use LoggerAwareTrait;

    private
        $queueName,
        $description,
        $consumer,
        $workerLogger,
        $worker;

    public function __construct(\Closure $worker, Consumer $consumer, $queueName)
    {
        $this->worker = $worker;
        $this->consumer = $consumer;
        $this->queueName = $queueName;
        $this->description = null;
        $this->logger = new NullLogger();
        $this->workerLogger = null;
    }

    public function getWorker()
    {
        if($this->worker instanceof \Closure)
        {
            $closure = $this->worker;
            $this->worker = $closure();
            $this->worker->setLogger($this->getLoggerForWorker());
        }

        return $this->worker;
    }

    private function getLoggerForWorker()
    {
        if($this->workerLogger instanceof LoggerInterface)
        {
            return $this->workerLogger;
        }

        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->consumer->setLogger($logger);

        return $this;
    }

    public function setWorkerLogger(LoggerInterface $logger)
    {
        $this->workerLogger = $logger;

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

    public function getQueueName()
    {
        return $this->queueName;
    }
}
