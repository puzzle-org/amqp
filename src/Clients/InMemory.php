<?php

namespace Puzzle\AMQP\Clients;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\WritableMessage;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Clients\Processors\MessageProcessorAware;

class InMemory implements Client
{
    use
        \Psr\Log\LoggerAwareTrait,
        MessageProcessorAware;

    private
        $sentMessages;

    public function __construct()
    {
        $this->sentMessages = [];
        $this->logger = new NullLogger();
    }

    public function publish(string $exchangeName, WritableMessage $message): bool
    {
        $this->updateMessageAttributes($message);
        $this->saveMessage($exchangeName, $message);
        
        return true;
    }

    private function updateMessageAttributes(WritableMessage $message): void
    {
        $message->setAttribute('app_id', 'memory');
        $message->addHeader('routing_key', $message->getRoutingKey());
        
        $this->onPublish($message);
    }

    public function getQueue(string $queueName): \AMQPQueue
    {
        throw new \RuntimeException('This AMQP Client must be used only for sending purpose');
    }

    public function getExchange(?string $exchangeName = null, string $type = AMQP_EX_TYPE_TOPIC): \AMQPExchange
    {
        throw new \RuntimeException('This AMQP Client must be used only for sending purpose');
    }

    private function saveMessage($exchangeName, WritableMessage $message)
    {
        $this->sentMessages[] = [
            'exchange' => $exchangeName,
            'message' => $message
        ];
    }

    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    public function dropSentMessages(): self
    {
        $this->sentMessages = [];

        return $this;
    }
}
