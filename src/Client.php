<?php

declare(strict_types = 1);

namespace Puzzle\AMQP;

use Psr\Log\LoggerAwareInterface;
use Puzzle\AMQP\Messages\Processor;

interface Client extends LoggerAwareInterface
{
    public function publish(string $exchangeName, WritableMessage $message): bool;

    public function getQueue(string $queueName): \AMQPQueue;

    public function getExchange(?string $exchangeName): \AMQPExchange;

    public function setMessageProcessors(array $processors): self;

    public function appendMessageProcessor(Processor $processor): self;
}
