<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Clients;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\Clients\MemoryManagementStrategies\NullMemoryManagementStrategy;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\Message;

class ChunkedMessageClient
{
    const string
        DEFAULT_ROUTING_KEY_PREFIX = 'part';

    private string
        $prefix;
    private Client
        $client;
    private ?MemoryManagementStrategy
        $memory;

    public function __construct(Client $client, ?MemoryManagementStrategy $memory = null)
    {
        $this->changeRoutingKeyPrefix(self::DEFAULT_ROUTING_KEY_PREFIX);

        if(! $memory instanceof MemoryManagementStrategy)
        {
            $memory = new NullMemoryManagementStrategy();
        }

        $this->memory = $memory;
        $this->client = $client;
    }

    public function changeRoutingKeyPrefix(string $prefix): void
    {
        $prefix = rtrim($prefix, '.');

        $this->prefix = $prefix . ".";
    }

    public function publish(string $exchangeName, WritableMessage $chunkedMessage)
    {
        $streamedContent = $chunkedMessage->getBodyInTransportFormat();

        if(! $streamedContent instanceof \Generator)
        {
            return $this->client->publish($exchangeName, $chunkedMessage);
        }

        $this->memory->init();

        $allowCompression = $chunkedMessage->isCompressionAllowed();

        foreach($streamedContent as $chunk)
        {
            $message = new Message($this->prefix . $chunkedMessage->getRoutingKey());
            $message->setBinary($chunk->getContent());
            $message->allowCompression($allowCompression);

            $message->addHeaders($chunk->getHeaders());
            $message->addHeaders([
                'message' => [
                    'routingKey' => $chunkedMessage->getRoutingKey(),
                    'contentType' => $chunkedMessage->getContentType(),
                ],
            ]);
            $message->addHeaders($chunkedMessage->getHeaders());

            $this->client->publish($exchangeName, $message);

            $size = $chunk->size();

            unset($message, $chunk);

            $this->memory->manage($size);
        }
    }
}
