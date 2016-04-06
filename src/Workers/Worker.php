<?php

namespace Puzzle\AMQP\Workers;

use Psr\Log\LoggerAwareInterface;
use Puzzle\AMQP\ReadableMessage;

interface Worker extends LoggerAwareInterface
{
    public function process(ReadableMessage $message);
}