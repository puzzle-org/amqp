<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\WritableMessage;

interface OnPublishProcessor extends Processor
{
    public function onPublish(WritableMessage $message): void;
}
