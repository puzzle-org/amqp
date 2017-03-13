<?php

namespace Puzzle\AMQP\Workers;

use Psr\Log\InvalidArgumentException;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\MessageDecoder;

class MessageAdapter implements ReadableMessage
{
    private
        $message,
        $body;

    public function __construct(\Swarrot\Broker\Message $message)
    {
        $this->message = $message;
        $this->body = (new MessageDecoder())->decode($this);
    }

    public function getRoutingKey()
    {
        return $this->getAttribute('routing_key');
    }

    public function getContentType()
    {
        return $this->getAttribute('content_type');
    }

    public function getAppId()
    {
        return $this->getAttribute('app_id');
    }

    public function getHeaders()
    {
        return $this->getAttribute('headers');
    }
    
    public function getBody()
    {
        return $this->body;
    }

    public function getDecodedBody()
    {
        return $this->body->decode();
    }

    public function getRawBody()
    {
        return $this->message->getBody();
    }

    public function getFlags()
    {
        throw new \LogicException('Consumed messages have no flags');
    }

    public function getAttribute($attributeName)
    {
        $messageProperties = $this->message->getProperties();
        if(array_key_exists($attributeName, $messageProperties))
        {
            return $messageProperties[$attributeName];
        }

        throw new InvalidArgumentException(sprintf('Property "%s" is unknown or is not a message property', $attributeName));
    }

    public function __toString()
    {
        return json_encode(array(
            'routing_key' => $this->getRoutingKey(),
            'body' => (string) $this->body,
            'attributes' => $this->message->getProperties(),
        ));
    }
    
    public function getService()
    {
        return $this->getHeader('service');
    }

    public function getAction()
    {
        return $this->getHeader('action');
    }

    public function getAuthor()
    {
        return $this->getHeader('author');
    }

    private function getHeader($headerName)
    {
        $headers = $this->getHeaders();
        if(array_key_exists($headerName, $headers))
        {
            return $headers[$headerName];
        }

        return null;
    }

    public function getAttributes()
    {
        return $this->message->getProperties();
    }

    public function isLastRetry($retryOccurence = \Puzzle\AMQP\Consumers\Retry::DEFAULT_RETRY_OCCURENCE)
    {
        $retryHeader = $this->getHeader(\Puzzle\AMQP\Consumers\Retry::DEFAULT_RETRY_HEADER);

        return (!empty($retryHeader) && (int) $retryHeader === $retryOccurence);
    }

    public function getRoutingKeyFromHeader()
    {
        $headers = $this->getHeaders();

        if(! array_key_exists('routing_key', $headers))
        {
            return null;
        }

        return $headers['routing_key'];
    }
}
