<?php

namespace Puzzle\AMQP;

use Psr\Log\LoggerAwareInterface;

interface Client extends LoggerAwareInterface
{
    public function publish($exchangeName, WritableMessage $message);

    public function getQueue($queueName);

    public function getExchange($exchangeName);
}
