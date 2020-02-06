<?php

namespace Puzzle\AMQP\Messages;

interface Body
{
    /**
     * In the same format than before message sending (before encoding in AMQP layer)
     *
     * @return mixed
     */
    public function inOriginalFormat();

    /**
     * In the same format than in AMQP layer
     *
     * @return ?string|\Generator
     */
    public function asTransported();

    public function getContentType(): string;

    public function isChunked(): bool;

    public function __toString(): string;
}
