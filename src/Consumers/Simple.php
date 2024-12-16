<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Consumers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;
use Swarrot\Processor\MaxExecutionTime\MaxExecutionTimeProcessor;
use Swarrot\Processor\Ack\AckProcessor;

class Simple extends AbstractConsumer
{
    public function consume(ProcessorInterface $processor, Client $client, string $queue): void
    {
        parent::consume($processor, $client, $queue);

        $stack = $this->getBaseStack()
            ->push(MaxExecutionTimeProcessor::class, $this->logger)
            ->push(AckProcessor::class, $this->messageProvider, $this->logger)
        ;

        $consumer = $this->getSwarrotConsumer($stack);

        $consumer->consume([
            'max_execution_time' => self::DEFAULT_MAX_EXECUTION_TIME
        ]);
    }
}
