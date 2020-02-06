<?php

namespace Puzzle\AMQP;

use Swarrot\Processor\ProcessorInterface;
use Psr\Log\LoggerAwareInterface;
use Puzzle\AMQP\Workers\WorkerContext;

interface Consumer extends LoggerAwareInterface
{
    public const
        DEFAULT_MAX_EXECUTION_TIME = 3600; //in seconds

    public function consume(ProcessorInterface $processor, Client $client, WorkerContext $workerContext);
}
