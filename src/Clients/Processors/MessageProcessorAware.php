<?php

namespace Puzzle\AMQP\Clients\Processors;

use Puzzle\AMQP\Messages\Processor;
use Puzzle\AMQP\WritableMessage;

trait MessageProcessorAware
{
    private
        $messageProcessors = [];
    
    public function appendMessageProcessor(Processor $processor)
    {
        $this->messageProcessors[] = $processor;
        
        return $this;
    }
    
    private function onPublish(WritableMessage $message)
    {
        foreach($this->messageProcessors as $messageProcessor)
        {
            $messageProcessor->onPublish($message);
        }
    }
}
