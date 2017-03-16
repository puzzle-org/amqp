<?php

namespace Puzzle\AMQP\Clients\Decorators;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\WritableMessage;
use Psr\Log\LoggerAwareTrait;

class PrefixedQueueNamesClient implements Client
{
    const
        DELIMITER = '.';

    use LoggerAwareTrait;

    private
        $client,
        $queueNamePrefix;

    public function __construct(Client $client, $queueNamePrefix)
    {
        $this->client = $client;
        $this->queueNamePrefix = $queueNamePrefix;
    }

    public function publish($exchangeName, WritableMessage $message)
    {
        return $this->client->publish($exchangeName, $message);
    }

    public function getQueue($queueName)
    {
        $prefixedQueueName = $this->computePrefixedQueueName($queueName);

        return $this->client->getQueue($prefixedQueueName);
    }

    public function getExchange($exchangeName)
    {
        return $this->client->getExchange($exchangeName);
    }

    private function computePrefixedQueueName($queueName)
    {
        $queueNameParts = [];

        if(! empty($this->queueNamePrefix))
        {
            $queueNameParts[] = trim($this->queueNamePrefix);
        }

        $queueNameParts[] = $queueName;

        return implode(self::DELIMITER, $queueNameParts);
    }
}
