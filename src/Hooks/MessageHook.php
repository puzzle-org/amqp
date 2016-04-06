<?php

namespace Puzzle\AMQP\Hooks;

use Puzzle\AMQP\ReadableMessage;

interface MessageHook
{
    public function process(array $body);
}