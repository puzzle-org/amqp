<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;

class EmptyBody implements Body
{
    public function inOriginalFormat(): string
    {
        return '';
    }

    public function asTransported(): string
    {
        return '';
    }

    public function getContentType(): string
    {
        return ContentType::EMPTY_CONTENT;
    }

    public function __toString(): string
    {
        return '';
    }

    public function isChunked(): false
    {
        return false;
    }
}
