<?php

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\Body;

class ReadableMessageModifier
{
    private
        $originalMessage,
        $newRoutingKey,
        $newBody,
        $newAttributeValues,
        $newHeaders,
        $droppedHeaders;
    
    public function __construct(ReadableMessage $originalMessage)
    {
        $this->originalMessage = $originalMessage;
        
        $this->newRoutingKey= $originalMessage->getRoutingKey();
        $this->newBody = null;
        
        $this->newAttributeValues = [];
        $this->newHeaders = [];
        $this->droppedHeaders = [];
    }
    
    public function changeRoutingKey($newRoutingKey)
    {
        $this->newRoutingKey = $newRoutingKey;
        
        return $this;
    }
    
    public function changeBody(Body $newBody)
    {
        $this->newBody = $newBody;
        
        return $this;
    }
    
    public function changeAttribute($name, $value)
    {
        $this->newAttributeValues[$name] = $value;
        
        return $this;
    }
    
    public function addHeader($headerName, $value)
    {
        $this->newHeaders[$headerName] = $value;
        
        return $this;
    }
    public function dropHeader($headerName)
    {
        $this->droppedHeaders[] = $headerName;
        
        return $this;
    }
    
    public function build()
    {
        $properties = $this->buildAttributes(
            $this->originalMessage->getAttributes()
        );
        
        $properties['headers'] = $this->buildHeaders(
            $this->extractHeaders($properties)
        );
        
        return new MessageAdapter(
            new \Swarrot\Broker\Message($this->buildBody(), $properties)
        );
    }
    
    private function extractHeaders(array $properties)
    {
        $headers = [];

        if(isset($properties['headers']))
        {
            $headers = $properties['headers'];
        }
        
        return $headers;
    }
    
    private function buildBody()
    {
        if($this->newBody instanceof Body)
        {
            return $this->newBody->asTransported();
        }
        
        return $this->originalMessage->getBodyAsTransported();
    }
    
    private function buildAttributes(array $attributes)
    {
        if($this->newBody instanceof Body)
        {
            $attributes['content_type'] = $this->newBody->getContentType();
        }
        
        $attributes['routing_key'] = $this->newRoutingKey;
        
        foreach($this->newAttributeValues as $name => $value)
        {
            if(isset($attributes[$name]))
            {
                $attributes[$name] = $value;
            }
        }
        
        return $attributes;
    }
    
    private function buildHeaders(array $headers)
    {
        foreach($this->droppedHeaders as $droppedHeader)
        {
            if(isset($headers[$droppedHeader]))
            {
                unset($headers[$droppedHeader]);
            }
        }
        
        foreach($this->newHeaders as $name => $value)
        {
            $headers[$name] = $value;
        }
        
        return $headers;
    }
}
