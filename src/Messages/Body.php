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
     * @return string
     */
    public function asTransported();

    /**
     * @return string
     */
    public function getContentType();

    /**
     * @return bool
     */
    public function isChunked();

    /**
     * @return string
     */
    public function __toString();
}
