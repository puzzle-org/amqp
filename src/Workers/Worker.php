<?php

namespace Puzzle\AMQP\Workers;

use Psr\Log\LoggerAwareInterface;
use Puzzle\AMQP\ReadableMessage;

interface Worker extends LoggerAwareInterface
{
    /**
     * @return void
     */
    public function process(ReadableMessage $message);
}
