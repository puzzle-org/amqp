<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Footprintable;

class Text implements Body, Footprintable
{
    private
        $content;
    
    public function __construct($text = '')
    {
        $this->changeText($text);
    }
    
    public function inOriginalFormat()
    {
        return $this->content;
    }

    public function asTransported()
    {
        return $this->content;
    }
    
    public function getContentType()
    {
        return ContentType::TEXT;
    }
    
    public function __toString()
    {
        return $this->asTransported();
    }
    
    public function footprint()
    {
        return sha1($this->asTransported());
    }
    
    public function changeText($text)
    {
        $this->content = $text;
    }
    
    public function append(...$text)
    {
        foreach($text as $part)
        {
            $this->content .= $part;
        }
    }
}
