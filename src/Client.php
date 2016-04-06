<?php

namespace Puzzle\AMQP;

use Psr\Log\LoggerAwareInterface;
use Puzzle\AMQP\Collections;

interface Client extends LoggerAwareInterface
{
    public function publish($exchangeName, WritableMessage $message);

    public function getQueue($queueName);

    public function getExchange($exchangeName);
}
