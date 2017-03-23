<?php

namespace Puzzle\AMQP;

use Psr\Log\LoggerAwareInterface;

interface Client extends LoggerAwareInterface
{
    /**
     * @return boolean
     */
    public function publish($exchangeName, WritableMessage $message);

    /**
     * @return \AMQPQueue
     */
    public function getQueue($queueName);

    /**
     * @return \AMQPExchange
     */
    public function getExchange($exchangeName);
}
