<?php

namespace Puzzle\AMQP\Consumers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;
use Swarrot\Processor\Insomniac\InsomniacProcessor;
use Swarrot\Processor\Ack\AckProcessor;

class Insomniac extends AbstractConsumer
{
    public function consume(ProcessorInterface $processor, Client $client, string $queue): void
    {
        parent::consume($processor, $client, $queue);

        $stack = $this->getBaseStack()
            ->push(InsomniacProcessor::class, $this->logger)
            ->push(AckProcessor::class, $this->messageProvider, $this->logger)
        ;
        
        $consumer = $this->getSwarrotConsumer($stack);

        $consumer->consume([]);
    }
}
