<?php

namespace Puzzle\AMQP\Workers;

use Psr\Log\InvalidArgumentException;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Collections\MessageHookCollection;
use Puzzle\AMQP\Hooks\MessageHook;

class MessageAdapter implements ReadableMessage
{
    private
        $message,
        $decodedBody;

    public function __construct(\Swarrot\Broker\Message $message)
    {
        $this->message = $message;
        $this->decodedBody = $this->decodeBody();
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

    public function getDecodedBody()
    {
        return $this->decodedBody;
    }

    private function decodeBody()
    {
        $callable = $this->getFormatterStrategy($this->getContentType());
        $body = $this->getRawBody();

        if($callable instanceof \Closure)
        {
            $body = $callable($body);
        }

        return $body;
    }

    public function getRawBody()
    {
        return $this->message->getBody();
    }

    public function applyHooks(MessageHookCollection $messageHookCollection)
    {
        if(!empty($messageHookCollection))
        {
            foreach($messageHookCollection as $messageHook)
            {
                if($messageHook instanceof MessageHook)
                {
                    $this->decodedBody = $messageHook->process($this->decodedBody);
                }
            }
        }
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

    private function getFormatterStrategy($contentType)
    {
        $formatterStrategies = array(
            'application/json' => function($body) {
                return json_decode($body, true);
            },
        );

        if(array_key_exists($contentType, $formatterStrategies) === true)
        {
            return $formatterStrategies[$contentType];
        }
    }

    public function __toString()
    {
        return json_encode(array(
            'routing_key' => $this->getRoutingKey(),
            'body' => $this->getRawBody(),
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
