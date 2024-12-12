<?php

namespace Puzzle\AMQP\Messages\Bodies;

use Puzzle\AMQP\Messages\Body;
use Puzzle\AMQP\Messages\ContentType;

class Binary implements Body
{
    private mixed
        $content;

    public function __construct(mixed $content)
    {
        $this->changeContent($content);
    }

    public function inOriginalFormat(): mixed
    {
        return $this->content;
    }

    public function asTransported(): string|\Generator
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return ContentType::BINARY;
    }

    public function __toString(): string
    {
        return sprintf(
            '<binary stream of %d bytes>',
            strlen($this->content)
        );
    }

    public function changeContent(mixed$content): void
    {
        $this->content = $content;
    }

    public function isChunked(): bool
    {
        return false;
    }
}
