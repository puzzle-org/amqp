<?php

namespace Puzzle\AMQP\Consumers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;

class Simple extends AbstractConsumer
{
    public function consume(ProcessorInterface $processor, Client $client, WorkerContext $workerContext)
    {
        parent::consume($processor, $client, $workerContext);

        $stack = $this->getBaseStack()
            ->push('Swarrot\Processor\MaxExecutionTime\MaxExecutionTimeProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $this->messageProvider, $this->logger)
        ;

        $options = $this->getOptions();

        $consumer = $this->getSwarrotConsumer($stack);

        return $consumer->consume($options);
    }

    private function getOptions()
    {
        return array(
            'max_execution_time' => self::DEFAULT_MAX_EXECUTION_TIME
        );
    }
}
