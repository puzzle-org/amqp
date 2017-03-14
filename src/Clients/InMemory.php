<?php

namespace Puzzle\AMQP\Clients;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\InMemoryJson;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Collections;

class InMemory implements Client
{
    use \Psr\Log\LoggerAwareTrait;

    private
        $sentMessages;

    public function __construct()
    {
        $this->sentMessages = array();
        $this->logger = new NullLogger();
    }

    public function publish($exchangeName, WritableMessage $message)
    {
        $this->updateMessageAttributes($message);
        $this->saveMessage($exchangeName, $message);
    }

    private function updateMessageAttributes(WritableMessage $message)
    {
        $message->setAttribute('app_id', 'memory');
        $message->addHeader('routing_key', $message->getRoutingKey());
    }

    public function getQueue($queueName)
    {
        throw new \RuntimeException('This AMQP Client must be used only for sending purpose');
    }

    public function getExchange($exchangeName)
    {
        throw new \RuntimeException('This AMQP Client must be used only for sending purpose');
    }

    private function saveMessage($exchangeName, WritableMessage $message)
    {
        $this->sentMessages[] = array(
            'exchange' => $exchangeName,
            'message' => $message
        );
    }

    public function getSentMessages()
    {
        return $this->sentMessages;
    }

    public function dropSentMessages()
    {
        $this->sentMessages = array();

        return $this;
    }
}
