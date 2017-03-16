<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;

class NullBody implements Body
{
    public function format()
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

    public function decode()
    {
        return null;
    }
}
