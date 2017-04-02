<?php

namespace Puzzle\AMQP;

use Psr\Log\LoggerAwareInterface;
use Puzzle\AMQP\Messages\Processor;

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

    /**
     * @return self
     */
    public function setMessageProcessors(array $processors);

    /**
     * @return self
     */
    public function appendMessageProcessor(Processor $processor);
}
