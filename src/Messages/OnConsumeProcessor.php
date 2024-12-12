<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\ReadableMessage;

interface OnConsumeProcessor extends Processor
{
    public function onConsume(ReadableMessage $message): ReadableMessage;
}
