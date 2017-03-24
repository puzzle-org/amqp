<?php

namespace Puzzle\AMQP\Messages\Processors;

use Puzzle\AMQP\Messages\Processor;
use Puzzle\AMQP\WritableMessage;

class AddHeader implements Processor
{
    private
        $headers;
    
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
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
