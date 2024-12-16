<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

trait MessageAdapterFactoryAware
{
    private ?MessageAdapterFactory
        $messageAdapterFactory;
    
    public function setMessageAdapterFactory(?MessageAdapterFactory $factory = null): static
    {
        $this->messageAdapterFactory = $factory;
        
        return $this;
    }
    
    public function createMessageAdapter(\Swarrot\Broker\Message $message): MessageAdapter
    {
        $factory = $this->messageAdapterFactory;
        
        if(! $factory instanceof MessageAdapterFactory)
        {
            $factory = new MessageAdapterFactory();
        }
        
        return $factory->build($message);
    }
}
