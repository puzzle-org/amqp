<?php

namespace Puzzle\AMQP\Clients\Processors;

use Puzzle\AMQP\Messages\Processor;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\OnPublishProcessor;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\OnConsumeProcessor;

trait MessageProcessorAware
{
    private
        $messageProcessors = [];
    
    public function setMessageProcessors(array $processors)
    {
        $this->messageProcessors = [];

        foreach($processors as $processor)
        {
            if($processor instanceof Processor)
            {
                $this->appendMessageProcessor($processor);
            }
        }

        return $this;
    }

    public function appendMessageProcessor(Processor $processor)
    {
        $this->messageProcessors[] = $processor;
        
        return $this;
    }
    
    private function onPublish(WritableMessage $message)
    {
        foreach($this->messageProcessors as $messageProcessor)
        {
            if($messageProcessor instanceof OnPublishProcessor)
            {
                $messageProcessor->onPublish($message);
            }
        }
    }

    private function onConsume(ReadableMessage $message)
    {
        foreach(array_reverse($this->messageProcessors) as $messageProcessor)
        {
            if($messageProcessor instanceof OnConsumeProcessor)
            {
                $message = $messageProcessor->onConsume($message);
            }
        }

        return $message;
    }
}
