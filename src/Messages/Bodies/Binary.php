<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;

class Binary implements Body
{
    private
        $body;
    
    public function __construct($content)
    {
        $this->changeContent($content);
    }
    
    public function format()
    {
        return $this->body;
    }
    
    public function footprint()
    {
        return uniqid(true);
    }
    
    public function changeContent($content)
    {
        $this->body = $content;
    }
    
    public function getContentType()
    {
        return ContentType::BINARY;
    }
    
    public function __toString()
    {
        return sprintf(
            '<binary stream of %d bytes>',
            strlen($this->body)
        );
    }
    
    public function decode()
    {
        return $this->body;
    }
}
