<?php

namespace Puzzle\AMQP\Workers;

trait MessageAdapterFactoryAware
{
    private
        $messageAdapterFactory;
    
    public function setMessageAdapterFactory(?MessageAdapterFactory $factory = null)
    {
        $this->messageAdapterFactory = $factory;
        
        return $this;
    }
    
    public function createMessageAdapter(\Swarrot\Broker\Message $message)
    {
        $factory = $this->messageAdapterFactory;
        
        if(! $factory instanceof MessageAdapterFactory)
        {
            $factory = new MessageAdapterFactory();
        }
        
        return $factory->build($message);
    }
}
