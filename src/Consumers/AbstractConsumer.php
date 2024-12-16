<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Consumers;

use Puzzle\AMQP\Consumer;
use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Consumer as SwarrotConsumer;
use Swarrot\Processor\SignalHandler\SignalHandlerProcessor;
use Swarrot\Processor\Stack;
use Swarrot\Processor\ProcessorInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;
use Swarrot\Processor\ExceptionCatcher\ExceptionCatcherProcessor;

abstract class AbstractConsumer implements Consumer
{
    use LoggerAwareTrait;

    private Client
        $client;
    private ProcessorInterface
        $processor;
    private string
        $queue;
    protected MessageProviderInterface
        $messageProvider;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function consume(ProcessorInterface $processor, Client $client, string $queue): void
    {
        $this->processor = $processor;
        $this->client = $client;
        $this->queue = $queue;
        $this->setMessageProvider();
    }

    private function setMessageProvider(): void
    {
        $this->messageProvider = new PeclPackageMessageProvider(
            $this->client->getQueue($this->queue)
        );
    }

    protected function getBaseStack(): Stack\Builder
    {
        return (new Stack\Builder())
            ->push(SignalHandlerProcessor::class, $this->logger)
            ->push(ExceptionCatcherProcessor::class, $this->logger)
        ;
    }

    protected function getSwarrotConsumer(Stack\Builder $stack): SwarrotConsumer
    {
        return new SwarrotConsumer(
            $this->messageProvider,
            $stack->resolve($this->processor),
            null,
            $this->logger
        );
    }
}
