<?php

namespace Puzzle\AMQP\Workers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\Pieces\EventDispatcher\NullEventDispatcher;
use Puzzle\Pieces\EventDispatcher\EventDispatcherAware;
use Puzzle\AMQP\Clients\Processors\MessageProcessorAware;
use Symfony\Contracts\EventDispatcher\Event;

class ProcessorInterfaceAdapter implements ProcessorInterface
{
    use
        EventDispatcherAware,
        MessageAdapterFactoryAware,
        MessageProcessorAware;
    
    private
        $workerContext;
    
    public function __construct(WorkerContext $workerContext)
    {
        $this->workerContext = $workerContext;
        $this->eventDispatcher = new NullEventDispatcher();
        $this->messageAdapterFactory = null;
    }
    
    public function process(\Swarrot\Broker\Message $message, array $options): bool
    {
        $message = $this->createMessageAdapter($message);
        $message = $this->onConsume($message);
        
        $this->workerContext->getLogger()->debug((string) $message);
        
        $this->onWorkerProcess();

        try
        {
            $processResult = $this->workerContext->getWorker()->process($message);
        }
        catch(\Throwable $exception)
        {
            $this->onWorkerProcessed();

            if($exception instanceof \Error)
            {
                $exception = new \ErrorException($exception->getMessage(), $exception->getCode(), E_ERROR, $exception->getFile(), $exception->getLine(), $exception);
            }

            throw $exception;
        }

        $this->onWorkerProcessed();

        return $processResult;
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
