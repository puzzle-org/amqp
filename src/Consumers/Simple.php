<?php

namespace Puzzle\AMQP\Consumers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;

class Simple extends AbstractConsumer
{
    public function consume(ProcessorInterface $processor, Client $client, string $queue): void
    {
        parent::consume($processor, $client, $queue);

        $stack = $this->getBaseStack()
            ->push('Swarrot\Processor\MaxExecutionTime\MaxExecutionTimeProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $this->messageProvider, $this->logger)
        ;

        $consumer = $this->getSwarrotConsumer($stack);

        $consumer->consume([
            'max_execution_time' => self::DEFAULT_MAX_EXECUTION_TIME
        ]);
    }
}
