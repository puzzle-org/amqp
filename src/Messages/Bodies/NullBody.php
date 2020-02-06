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

    public function asTransported(): ?string
    {
        return null;
    }

    public function getContentType(): string
    {
        return ContentType::EMPTY_CONTENT;
    }

    public function __toString(): string
    {
        return '';
    }

    public function isChunked(): bool
    {
        return false;
    }
}
