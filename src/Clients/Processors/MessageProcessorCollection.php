<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Clients\Processors;

use Puzzle\AMQP\Messages\Processor;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\OnPublishProcessor;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\OnConsumeProcessor;

class MessageProcessorCollection
{
    private array
        $messageProcessors = [];
    
    public function setMessageProcessors(array $processors): void
    {
        $this->messageProcessors = [];

        foreach($processors as $processor)
        {
            if($processor instanceof Processor)
            {
                $this->appendMessageProcessor($processor);
            }
        }
    }

    public function appendMessageProcessor(Processor $processor): void
    {
        $this->messageProcessors[] = $processor;
    }
    
    public function onPublish(WritableMessage $message): void
    {
        foreach($this->messageProcessors as $messageProcessor)
        {
            if($messageProcessor instanceof OnPublishProcessor)
            {
                $messageProcessor->onPublish($message);
            }
        }
    }

    public function onConsume(ReadableMessage $message): ReadableMessage
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
