<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\WritableMessage;

interface Processor
{
    /**
     * @return void
     */
    public function onPublish(WritableMessage $message);
}
