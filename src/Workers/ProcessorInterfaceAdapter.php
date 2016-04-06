<?php

namespace Puzzle\AMQP\Workers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\Lib4T\EventDispatcher\NullEventDispatcher;
use Puzzle\Lib4T\EventDispatcher\EventDispatcherAware;

class ProcessorInterfaceAdapter implements ProcessorInterface
{
    use EventDispatcherAware;
    
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
        
        $hooks = $this->workerContext->getMessageHooks();
        
        if(!empty($hooks))
        {
            $message->applyHooks($hooks);
        }
        
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
