<?php

namespace Puzzle\AMQP;

use Psr\Log\LoggerAwareInterface;
use Puzzle\AMQP\Messages\Processor;

interface Client extends LoggerAwareInterface
{
    public function publish(string $exchangeName, WritableMessage $message): bool;

    public function getQueue(string $queueName): \AMQPQueue;

    public function getExchange(?string $exchangeName = null, string $type = AMQP_EX_TYPE_TOPIC): \AMQPExchange;

    public function setMessageProcessors(array $processors);

    public function appendMessageProcessor(Processor $processor);
}
