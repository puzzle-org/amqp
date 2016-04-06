<?php

namespace Puzzle\AMQP\Consumers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;

class Insomniac extends AbstractConsumer
{
    public function consume(ProcessorInterface $processor, Client $client, WorkerContext $workerContext)
    {
        parent::consume($processor, $client, $workerContext);

        $stack = $this->getBaseStack()
            ->push('Swarrot\Processor\Insomniac\InsomniacProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $this->messageProvider, $this->logger)
        ;
        
        $options = $this->getOptions();

        $consumer = $this->getSwarrotConsumer($stack);

        return $consumer->consume($options);
    }

    private function getOptions()
    {
        return array();
    }
}
