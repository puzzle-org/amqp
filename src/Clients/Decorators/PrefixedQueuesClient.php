<?php

namespace Puzzle\AMQP\Clients\Decorators;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\WritableMessage;
use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Messages\Processor;

class PrefixedQueuesClient implements Client
{
    const string
        DELIMITER = '.';

    use LoggerAwareTrait;

    private Client
        $client;
    private ?string
        $queueNamePrefix;

    public function __construct(Client $client, ?string $queueNamePrefix)
    {
        $this->client = $client;
        $this->queueNamePrefix = $queueNamePrefix;
    }

    public function publish(string $exchangeName, WritableMessage $message): bool
    {
        return $this->client->publish($exchangeName, $message);
    }

    public function getQueue(string $queueName): \AMQPQueue
    {
        $prefixedQueueName = $this->computePrefixedQueueName($queueName);

        return $this->client->getQueue($prefixedQueueName);
    }

    public function getExchange(?string $exchangeName): \AMQPExchange
    {
        return $this->client->getExchange($exchangeName);
    }

    private function computePrefixedQueueName($queueName): string
    {
        $queueNameParts = [];

        if(! empty($this->queueNamePrefix))
        {
            $queueNameParts[] = trim($this->queueNamePrefix);
        }

        $queueNameParts[] = $queueName;

        return implode(self::DELIMITER, $queueNameParts);
    }
    
    public function appendMessageProcessor(Processor $processor): Client
    {
        return $this->client->appendMessageProcessor($processor);
    }

    public function setMessageProcessors(array $processors): Client
    {
        return $this->client->setMessageProcessors($processors);
    }
}
