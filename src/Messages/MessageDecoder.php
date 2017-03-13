<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\Bodies\Text;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\Bodies\Binary;
use Puzzle\AMQP\Messages\Bodies\NullBody;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class MessageDecoder
{
    use LoggerAwareTrait;
    
    public function __construct()
    {
        $this->logger = new NullLogger();
    }
    
    public function decode(ReadableMessage $message)
    {
        $bodyContent = $message->getRawBody();
        $contentType = $message->getContentType();
        
        switch($contentType)
        {
            case ContentType::TEXT:
                return new Text($bodyContent);
                
            case ContentType::JSON:
                $body = new Json();
                $body->changeContentWithJson($bodyContent);
                
                return $body;
                
            case ContentType::BINARY:
                return new Binary($bodyContent);
        }
        
        $this->logger->warning(__CLASS__ . ": unknown content-type, use empty body");
        
        return new NullBody();
    }
}
