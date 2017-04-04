<?php

namespace Puzzle\AMQP\Workers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\Pieces\EventDispatcher\NullEventDispatcher;
use Puzzle\Pieces\EventDispatcher\EventDispatcherAware;
use Puzzle\AMQP\Clients\Processors\MessageProcessorAware;

class ProcessorInterfaceAdapter implements ProcessorInterface
{
    use
        EventDispatcherAware,
        MessageProcessorAware;
    
    private
        $workerContext;
    
    public function __construct(WorkerContext $workerContext)
    {
        $this->workerContext = $workerContext;
        $this->eventDispatcher = new NullEventDispatcher();
    }
    
    public function process(\Swarrot\Broker\Message $message, array $options)
    {
        $message = new MessageAdapter($message);
        $message = $this->onConsume($message);
        
        $this->workerContext->getLogger()->debug((string) $message);
        
        $this->onWorkerProcess();

        try
        {
            $processResult = $this->workerContext->getWorker()->process($message);
        }
        catch(\Exception $exception)
        {
            $this->onWorkerProcessed();

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
