<?php

namespace Puzzle\AMQP\Workers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\Pieces\EventDispatcher\NullEventDispatcher;
use Puzzle\Pieces\EventDispatcher\EventDispatcherAware;
use Puzzle\AMQP\Clients\Processors\MessageProcessorAware;
use Symfony\Contracts\EventDispatcher\Event;

final class ProcessorInterfaceAdapter implements ProcessorInterface
{
    use
        EventDispatcherAware,
        MessageAdapterFactoryAware,
        MessageProcessorAware;

    private Worker
        $worker;

    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
        $this->eventDispatcher = new NullEventDispatcher();
        $this->messageAdapterFactory = null;
    }

    public function process(\Swarrot\Broker\Message $message, array $options): bool
    {
        $message = $this->createMessageAdapter($message);
        $message = $this->onConsume($message);

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

    private function onWorkerProcess()
    {
        $this->eventDispatcher->dispatch('worker.process');
    }

    private function onWorkerProcessed()
    {
        $this->eventDispatcher->dispatch('worker.processed');
    }
}
