<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Footprintable;
use Puzzle\Pieces\StringManipulation;

class Text implements Body, Footprintable
{
    use
        StringManipulation;

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
        if(! $this->isConvertibleToString($text))
        {
            throw new \LogicException('Body of type Text must be string convertible.');
        }

        $this->content = (string) $text;
    }

    public function append(...$text)
    {
        foreach($text as $part)
        {
            $this->content .= $part;
        }
    }

    public function isChunked()
    {
        return false;
    }
}
