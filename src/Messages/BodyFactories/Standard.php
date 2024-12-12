<?php

namespace Puzzle\AMQP\Messages\BodyFactories;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\TypedBodyFactories;
use Puzzle\AMQP\Messages\BodyFactory;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Bodies\EmptyBody;
use Puzzle\AMQP\Messages\TypedBodyFactory;

class Standard implements BodyFactory
{
    use LoggerAwareTrait;
    
    private array
        $factories;
    
    public function __construct()
    {
        $this->initializeFactories();
        $this->logger = new NullLogger();
    }
    
    private function initializeFactories(): void
    {
        $this->factories = [
            ContentType::TEXT => new TypedBodyFactories\Text(),
            ContentType::JSON => new TypedBodyFactories\Json(),
            ContentType::BINARY => new TypedBodyFactories\Binary(),
        ];
    }
    
    public function handleContentType($contentType, TypedBodyFactory $factory): static
    {
        $this->factories[$contentType] = $factory;
        
        return $this;
    }
    
    public function build($contentType, $contentAsTransported): Body
    {
        if(isset($this->factories[$contentType]))
        {
            return $this->factories[$contentType]->build($contentAsTransported);
        }
        
        $this->logger->warning(__CLASS__ . ": unknown content-type, use empty body");
        
        return new EmptyBody();
    }
}
