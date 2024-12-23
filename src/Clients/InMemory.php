<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Clients;

use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Clients\Processors\MessageProcessorCollection;
use Puzzle\AMQP\Messages\Processor;
use Puzzle\AMQP\WritableMessage;
use Psr\Log\NullLogger;

class InMemory implements Client
{
    use LoggerAwareTrait;

    private array
        $sentMessages;
    private MessageProcessorCollection
        $messageProcessors;

    public function __construct()
    {
        $this->sentMessages = [];
        $this->messageProcessors = new MessageProcessorCollection();

        $this->logger = new NullLogger();
    }

    public function setMessageProcessors(array $processors): static
    {
        $this->messageProcessors->setMessageProcessors($processors);

        return $this;
    }

    public function appendMessageProcessor(Processor $processor): static
    {
        $this->messageProcessors->appendMessageProcessor($processor);

        return $this;
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
        
        $this->messageProcessors->onPublish($message);
    }

    public function getQueue(string $queueName): \AMQPQueue
    {
        throw new \RuntimeException('This AMQP Client must be used only for sending purpose');
    }

    public function getExchange(?string $exchangeName): \AMQPExchange
    {
        throw new \RuntimeException('This AMQP Client must be used only for sending purpose');
    }

    private function saveMessage($exchangeName, WritableMessage $message): void
    {
        $this->sentMessages[] = array(
            'exchange' => $exchangeName,
            'message' => $message
        );
    }

    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    public function dropSentMessages(): static
    {
        $this->sentMessages = array();

        return $this;
    }
}
