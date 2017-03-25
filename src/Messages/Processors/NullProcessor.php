<?php

namespace Puzzle\AMQP\Messages\Processors;

use Puzzle\AMQP\Messages\Processor;
use Puzzle\AMQP\WritableMessage;

class NullProcessor implements Processor
{
    public function onPublish(WritableMessage $message)
    {
    }
}
