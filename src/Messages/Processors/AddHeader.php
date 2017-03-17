<?php

namespace Puzzle\AMQP\Messages\Processors;

use Puzzle\AMQP\WritableMessage;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Messages\OnPublishProcessor;

class AddHeader implements OnPublishProcessor
{
    use LoggerAwareTrait;
    
    private
        $headers;
    
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
        $this->logger = new NullLogger();
    }
    
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
        
        return $this;
    }
        
    public function onPublish(WritableMessage $message)
    {
        $alreadySetHeaders = $message->getHeaders();
        
        foreach($this->headers as $header => $value)
        {
            if(! isset($alreadySetHeaders[$header]))
            {
                $message->addHeader($header, $value);
            }
        }
    }
}
