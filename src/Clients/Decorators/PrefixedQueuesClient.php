<?php

namespace Puzzle\AMQP\Clients\Decorators;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\WritableMessage;
use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Messages\Processor;

class PrefixedQueuesClient implements Client
{
    const
        DELIMITER = '.';

    use LoggerAwareTrait;

    private
        $client,
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

    public function getExchange(?string $exchangeName = null, string $type = AMQP_EX_TYPE_TOPIC): \AMQPExchange
    {
        return $this->client->getExchange($exchangeName, $type);
    }

    public function computePrefixedQueueName(string $queueName): string
    {
        $queueNameParts = [];

        if(! empty($this->queueNamePrefix))
        {
            $queueNameParts[] = trim($this->queueNamePrefix);
        }

        $queueNameParts[] = $queueName;

        return implode(self::DELIMITER, $queueNameParts);
    }
    
    public function appendMessageProcessor(Processor $processor)
    {
        return $this->client->appendMessageProcessor($processor);
    }

    public function setMessageProcessors(array $processors)
    {
        return $this->client->setMessageProcessors($processors);
    }
}
