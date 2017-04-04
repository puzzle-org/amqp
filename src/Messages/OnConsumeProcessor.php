<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\ReadableMessage;

interface OnConsumeProcessor extends Processor
{
    /**
     * @return \Puzzle\AMQP\ReadableMessage
     */
    public function onConsume(ReadableMessage $message);
}
