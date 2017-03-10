<?php

namespace Puzzle\AMQP\Hooks;

interface MessageHook
{
    public function process(array $body);
}
