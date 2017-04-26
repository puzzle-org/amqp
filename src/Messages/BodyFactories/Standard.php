<?php

namespace Puzzle\AMQP\Messages\BodyFactories;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Messages\TypedBodyFactories;
use Puzzle\AMQP\Messages\BodyFactory;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Bodies\NullBody;

class Standard implements BodyFactory
{
    use LoggerAwareTrait;
    
    private
        $factories;
    
    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->initializeFactories();
    }
    
    private function initializeFactories()
    {
        $this->factories = [
            ContentType::TEXT => new TypedBodyFactories\Text(),
            ContentType::JSON => new TypedBodyFactories\Json(),
            ContentType::BINARY => new TypedBodyFactories\Binary(),
        ];
    }
    
    public function build($contentType, $contentAsTransported)
    {
        if(isset($this->factories[$contentType]))
        {
            return $this->factories[$contentType]->build($contentAsTransported);
        }
        
        $this->logger->warning(__CLASS__ . ": unknown content-type, use empty body");
        
        return new NullBody();
    }
}
