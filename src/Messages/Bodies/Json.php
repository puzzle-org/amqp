<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Footprintable;

class Json implements Body, Footprintable
{
    private mixed
        $jsonAsArray;

    public function __construct($content = [])
    {
        $this->changeContent($content);
    }

    public function inOriginalFormat(): mixed
    {
        return $this->jsonAsArray;
    }

    public function asTransported(): string
    {
        return \Puzzle\Pieces\Json::encode($this->jsonAsArray);
    }

    public function getContentType(): string
    {
        return ContentType::JSON;
    }

    public function __toString(): string
    {
        return $this->asTransported();
    }

    public function footprint(): string
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

    public function changeContentWithJson($json): void
    {
        $this->jsonAsArray = \Puzzle\Pieces\Json::decode($json, true);
    }

    public function isChunked(): false
    {
        return false;
    }
}
