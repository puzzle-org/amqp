<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;

class EmptyBody implements Body
{
    public function inOriginalFormat()
    {
        return '';
    }

    public function asTransported()
    {
        return '';
    }

    public function getContentType()
    {
        return ContentType::EMPTY_CONTENT;
    }

    public function __toString()
    {
        return '';
    }

    public function isChunked()
    {
        return false;
    }
}
