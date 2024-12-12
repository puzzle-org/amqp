<?php

namespace Puzzle\AMQP\Messages;

interface Body
{
    /**
     * In the same format as before message sending (before encoding in AMQP layer)
     */
    public function inOriginalFormat(): mixed;

    /**
     * In the same format as in AMQP layer
     */
    public function asTransported(): string|\Generator;

    public function getContentType(): string;

    public function isChunked(): bool;

    public function __toString(): string;
}
