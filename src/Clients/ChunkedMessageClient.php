<?php

namespace Puzzle\AMQP\Clients;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\Clients\MemoryManagementStrategies\NullMemoryManagementStrategy;
use Puzzle\AMQP\Messages\Message;

class ChunkedMessageClient
{
    private
        $client,
        $memory;

    public function __construct(Client $client, MemoryManagementStrategy $memory = null)
    {
        if(! $memory instanceof MemoryManagementStrategy)
        {
            $memory = new NullMemoryManagementStrategy();
        }

        $this->memory = $memory;
        $this->client = $client;
    }

    public function publish($exchangeName, Message $chunkedMessage)
    {
        $streamedContent = $chunkedMessage->getBodyInTransportFormat();

        if(! $streamedContent instanceof \Generator)
        {
            return $this->client->publish($exchangeName, $chunkedMessage);
        }

        $this->memory->init();

        $allowCompression = $chunkedMessage->isCompressionAllowed();

        foreach($streamedContent as $index => $chunk)
        {
            $message = new Message('part.' . $chunkedMessage->getRoutingKey());
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

            unset($message);
            unset($chunk);

            $this->memory->manage($size);
        }
    }
}
