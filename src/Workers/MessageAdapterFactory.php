<?php

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\Messages\BodyFactory;
use Puzzle\AMQP\Messages\BodyFactories\Standard;

class MessageAdapterFactory
{
    private BodyFactory
        $bodyFactory;

    public function __construct(?BodyFactory $bodyFactory = null)
    {
        if(! $bodyFactory instanceof BodyFactory)
        {
            $bodyFactory = new Standard();
        }
        
        $this->bodyFactory = $bodyFactory;
    }
    
    /**
     * @return \Puzzle\AMQP\Workers\MessageAdapter
     */
    public function build(\Swarrot\Broker\Message $message)
    {
        $adapter = new MessageAdapter($message);
        
        $body = $this->bodyFactory->build($adapter->getContentType(), $message->getBody());
        $adapter->setBody($body);
        
        return $adapter;
    }
}
