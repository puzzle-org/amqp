<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\Clients\Processors\MessageProcessorCollection;
use Puzzle\AMQP\Messages\Processor;
use Swarrot\Processor\ProcessorInterface;
use Puzzle\Pieces\EventDispatcher\NullEventDispatcher;
use Puzzle\Pieces\EventDispatcher\EventDispatcherAware;
use Puzzle\AMQP\Events\WorkerProcessed;
use Puzzle\AMQP\Events\WorkerProcess;

final class ProcessorInterfaceAdapter implements ProcessorInterface
{
    use
        EventDispatcherAware,
        MessageAdapterFactoryAware;

    private Worker
        $worker;
    private MessageProcessorCollection
        $messageProcessors;

    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
        $this->eventDispatcher = new NullEventDispatcher();
        $this->messageAdapterFactory = null;
        $this->messageProcessors = new MessageProcessorCollection();
    }

    public function setMessageProcessors(array $processors): static
    {
        $this->messageProcessors->setMessageProcessors($processors);

        return $this;
    }

    public function appendMessageProcessor(Processor $processor): static
    {
        $this->messageProcessors->appendMessageProcessor($processor);

        return $this;
    }

    public function process(\Swarrot\Broker\Message $message, array $options): bool
    {
        $message = $this->createMessageAdapter($message);
        $message = $this->messageProcessors->onConsume($message);

        $this->onWorkerProcess();

        try
        {
            $this->worker->process($message);
        }
        catch(\Throwable $t)
        {
            $this->onWorkerProcessed();

            if($t instanceof \Error)
            {
                $t = new \ErrorException(
                    $t->getMessage(),
                    $t->getCode(),
                    E_ERROR,
                    $t->getFile(),
                    $t->getLine(),
                    $t
                );
            }

            throw $t;
        }

        $this->onWorkerProcessed();

        return true;
    }

    private function onWorkerProcess(): void
    {
        $this->eventDispatcher->dispatch(WorkerProcess::NAME);
    }

    private function onWorkerProcessed(): void
    {
        $this->eventDispatcher->dispatch(WorkerProcessed::NAME);
    }
}
