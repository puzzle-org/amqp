<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

use Psr\Log\LoggerAwareInterface;
use Puzzle\AMQP\ReadableMessage;

interface Worker
{
    public function process(ReadableMessage $message): void;
}
