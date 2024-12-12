<?php

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\Body;

class ReadableMessageModifier
{
    private ReadableMessage
        $originalMessage;
    private string
        $newRoutingKey;
    private ?Body
        $newBody;
    private array
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
    
    public function changeRoutingKey(string $newRoutingKey): static
    {
        $this->newRoutingKey = $newRoutingKey;
        
        return $this;
    }
    
    public function changeBody(Body $newBody): static
    {
        $this->newBody = $newBody;
        
        return $this;
    }
    
    public function changeAttribute(string $name, mixed $value): static
    {
        $this->newAttributeValues[$name] = $value;
        
        return $this;
    }
    
    public function addHeader(string $headerName, mixed $value): static
    {
        $this->newHeaders[$headerName] = $value;
        
        return $this;
    }

    public function dropHeader(string $headerName): static
    {
        $this->droppedHeaders[] = $headerName;
        
        return $this;
    }
    
    public function build(): MessageAdapter
    {
        $properties = $this->buildAttributes(
            $this->originalMessage->getAttributes()
        );
        
        $properties['headers'] = $this->buildHeaders(
            $this->extractHeaders($properties)
        );
        
        $factory = new MessageAdapterFactory();
        
        return $factory->build(
            new \Swarrot\Broker\Message($this->buildBody(), $properties)
        );
    }
    
    private function extractHeaders(array $properties): mixed
    {
        $headers = [];

        if(isset($properties['headers']))
        {
            $headers = $properties['headers'];
        }
        
        return $headers;
    }
    
    private function buildBody(): mixed
    {
        if($this->newBody instanceof Body)
        {
            return $this->newBody->asTransported();
        }
        
        return $this->originalMessage->getBodyAsTransported();
    }
    
    private function buildAttributes(array $attributes): array
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
    
    private function buildHeaders(array $headers): array
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
