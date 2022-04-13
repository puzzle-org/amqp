<?php

namespace Puzzle\AMQP\Consumers;

use Puzzle\AMQP\Consumer;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Consumer as SwarrotConsumer;
use Swarrot\Processor\Stack;
use Swarrot\Processor\ProcessorInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;

abstract class AbstractConsumer implements Consumer
{
    use LoggerAwareTrait;

    protected
        $messageProvider;

    private
        $client,
        $processor,
        $workerContext;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function consume(ProcessorInterface $processor, Client $client, WorkerContext $workerContext)
    {
        $this->processor = $processor;
        $this->client = $client;
        $this->workerContext = $workerContext;
        $this->setMessageProvider();
    }

    private function setMessageProvider()
    {
        $this->messageProvider = new PeclPackageMessageProvider(
            $this->client->getQueue(
                $this->workerContext->getQueueName()
        ));
    }

    protected function getBaseStack()
    {
        $stack = (new Stack\Builder())
            ->push('Puzzle\AMQP\Processors\FatalErrorNack', $this->messageProvider)
            ->push('Swarrot\Processor\SignalHandler\SignalHandlerProcessor', $this->logger)
            ->push('Swarrot\Processor\ExceptionCatcher\ExceptionCatcherProcessor', $this->logger)
        ;

        return $stack;
    }

    protected function getSwarrotConsumer(Stack\Builder $stack)
    {
        return new SwarrotConsumer(
            $this->messageProvider,
            $stack->resolve($this->processor),
            null,
            $this->logger
        );
    }
}
