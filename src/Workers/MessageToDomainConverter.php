<?php

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\ReadableMessage;

interface MessageToDomainConverter
{
    public function convert(ReadableMessage $message);
}
