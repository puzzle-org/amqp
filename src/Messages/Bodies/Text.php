<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;

class Text implements Body
{
    private
        $content;
    
    public function __construct($text)
    {
        $this->changeText($text);
    }
    
    public function format()
    {
        return implode("\n", $this->content);
    }
    
    public function footprint()
    {
        return sha1($this->format());
    }
    
    public function changeText($text)
    {
        if(! is_array($text))
        {
            $text = array($text);
        }
        
        $this->content = $text;
    }
    
    public function append($text)
    {
        if(is_array($text))
        {
            $this->content = array_merge($this->content, $text);
            
            return;
        }
        
        $this->content[] = $text;
    }

    public function getContentType()
    {
        return ContentType::TEXT;
    }
    
    public function __toString()
    {
        return $this->format();
    }
    
    public function decode()
    {
        return $this->format();
    }
}
