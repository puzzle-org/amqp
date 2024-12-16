<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\WritableMessage;

interface OnPublishProcessor extends Processor
{
    public function onPublish(WritableMessage $message): void;
}
