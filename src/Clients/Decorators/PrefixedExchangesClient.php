<?php

namespace Puzzle\AMQP\Clients\Decorators;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\WritableMessage;
use Psr\Log\LoggerAwareTrait;

class PrefixedExchangesClient implements Client
{
    const
        DELIMITER = '.';
    
    use LoggerAwareTrait;
    
    private
        $client,
        $exchangesPrefix;
    
    public function __construct(Client $client, $exchangesPrefix)
    {
        $this->client = $client;
        $this->exchangesPrefix = $exchangesPrefix;
    }
    
    public function publish($exchangeName, WritableMessage $message)
    {
        return $this->client->publish(
            $this->computeExchangeName($exchangeName),
            $message
        );
    }
    
    private function computeExchangeName($exchangeName)
    {
        $exchangeParts = [];
        
        if(! empty($this->exchangesPrefix))
        {
            $exchangeParts[] = trim($this->exchangesPrefix);
        }

        $exchangeParts[] = $exchangeName;
        
        return trim(implode(self::DELIMITER, $exchangeParts));
            
    }
    
    public function getQueue($queueName)
    {
        return $this->client->getQueue($queueName);
    }
    
    public function getExchange($exchangeName)
    {
        return $this->client->getExchange($exchangeName);
    }
}
