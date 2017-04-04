<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\WritableMessage;

interface OnPublishProcessor extends Processor
{
    /**
     * @return void
     */
    public function onPublish(WritableMessage $message);
}
