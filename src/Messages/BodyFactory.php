<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\Messages\Bodies\Text;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\Bodies\Binary;
use Puzzle\AMQP\Messages\Bodies\NullBody;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class BodyFactory
{
    use LoggerAwareTrait;
    
    public function __construct()
    {
        $this->logger = new NullLogger();
    }
    
    public function create($contentType, $contentAsTransported)
    {
        switch($contentType)
        {
            case ContentType::TEXT:
                return new Text($contentAsTransported);
                
            case ContentType::JSON:
                $body = new Json();
                $body->changeContentWithJson($contentAsTransported);
                
                return $body;
                
            case ContentType::BINARY:
                return new Binary($contentAsTransported);
        }
        
        $this->logger->warning(__CLASS__ . ": unknown content-type, use empty body");
        
        return new NullBody();
    }
}
