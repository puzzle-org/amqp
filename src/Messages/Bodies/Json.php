<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Footprintable;

class Json implements Body, Footprintable
{
    private
        $jsonAsArray;

    public function __construct($content = [])
    {
        $this->changeContent($content);
    }

    public function inOriginalFormat()
    {
        return $this->jsonAsArray;
    }

    public function asTransported()
    {
        return \Puzzle\Pieces\Json::encode($this->jsonAsArray);
    }

    public function getContentType()
    {
        return ContentType::JSON;
    }

    public function __toString()
    {
        return $this->asTransported();
    }

    public function footprint()
    {
        return sha1($this->asTransported());
    }

    public function changeContent($content)
    {
        if(! is_array($content))
        {
            $content = array($content);
        }

        $this->jsonAsArray = $content;
    }

    public function changeContentWithJson($json)
    {
        $this->jsonAsArray = \Puzzle\Pieces\Json::decode($json, true);
    }

    public function isChunked()
    {
        return false;
    }
}
