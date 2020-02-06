<?php

namespace Puzzle\AMQP\Clients\Decorators;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\WritableMessage;
use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Messages\Processor;

class PrefixedExchangesClient implements Client
{
    const
        DELIMITER = '.';
    
    use LoggerAwareTrait;
    
    private
        $client,
        $exchangesPrefix;
    
    public function __construct(Client $client, ?string $exchangesPrefix)
    {
        $this->client = $client;
        $this->exchangesPrefix = $exchangesPrefix;
    }
    
    public function publish(string $exchangeName, WritableMessage $message): bool
    {
        return $this->client->publish(
            $this->computeExchangeName($exchangeName),
            $message
        );
    }
    
    private function computeExchangeName(string $exchangeName): string
    {
        $exchangeParts = [];
        
        if(! empty($this->exchangesPrefix))
        {
            $exchangeParts[] = trim($this->exchangesPrefix);
        }

        $exchangeParts[] = $exchangeName;
        
        return trim(implode(self::DELIMITER, $exchangeParts));
    }
    
    public function getQueue(string $queueName): \AMQPQueue
    {
        return $this->client->getQueue($queueName);
    }
    
    public function getExchange(?string $exchangeName = null, string $type = AMQP_EX_TYPE_TOPIC): \AMQPExchange
    {
        return $this->client->getExchange(
            $this->computeExchangeName($exchangeName),
            $type
        );
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
