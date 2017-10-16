<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;

class NullBody implements Body
{
    public function inOriginalFormat()
    {
        return null;
    }

    public function asTransported()
    {
        return null;
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
