<?php

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\Bodies\EmptyBody;
use Puzzle\AMQP\Messages\Body;
use Puzzle\Pieces\Exceptions\JsonEncodeError;
use Puzzle\Pieces\Json;

class MessageAdapter implements ReadableMessage
{
    private
        $message,
        $body;

    public function __construct(\Swarrot\Broker\Message $message)
    {
        $this->message = $message;
        $this->body = new EmptyBody();
    }
    
    public function setBody(Body $body)
    {
        $this->body = $body;
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

    public function getBodyInOriginalFormat()
    {
        return $this->body->inOriginalFormat();
    }
    
    public function getBodyAsTransported()
    {
        return $this->message->getBody();
    }

    public function getAttribute($attributeName)
    {
        $messageProperties = $this->message->getProperties();
        if(array_key_exists($attributeName, $messageProperties))
        {
            return $messageProperties[$attributeName];
        }

        throw new \InvalidArgumentException(sprintf('Property "%s" is unknown or is not a message property', $attributeName));
    }

    public function __toString(): string
    {
        try {
            return Json::encode([
                'routing_key' => $this->getRoutingKey(),
                'body' => (string) $this->body,
                'attributes' => $this->message->getProperties(),
            ]);
        }
        catch(JsonEncodeError $e)
        {
            return sprintf('Can\'t json encode the message. error: "%s"', $e->getMessage());
        }
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

        return ($retryHeader !== null && (int) $retryHeader >= $retryOccurence);
    }

    public function getRoutingKeyFromHeader()
    {
        return $this->getHeader('routing_key');
    }

    public function cloneIntoWritableMessage(WritableMessage $writable, $copyRoutingKey = false)
    {
        if($copyRoutingKey === true)
        {
            $writable->changeRoutingKey($this->getRoutingKey());
        }

        $writable->setBody($this->body);
        $writable->addHeaders($this->getHeaders());

        $attributes = $this->getAttributes();
        $skippedAttributes = array('timestamp', 'headers', 'app_id', 'routing_key');
        foreach($attributes as $attributeName => $value)
        {
            if(! in_array($attributeName, $skippedAttributes))
            {
                $writable->setAttribute($attributeName, $value);
            }
        }

        return $writable;
    }
}
