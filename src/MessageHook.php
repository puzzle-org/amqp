<?php

namespace Puzzle\AMQP;

interface MessageHook
{
    public function process(array $body);
}
