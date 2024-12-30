<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\Bodies\EmptyBody;
use Puzzle\AMQP\Messages\Body;
use Puzzle\Pieces\Exceptions\JsonEncodeError;
use Puzzle\Pieces\Json;

class MessageAdapter implements ReadableMessage
{
    private \Swarrot\Broker\Message
        $message;
    private Body
        $body;

    public function __construct(\Swarrot\Broker\Message $message)
    {
        $this->message = $message;
        $this->body = new EmptyBody();
    }
    
    public function setBody(Body $body): void
    {
        $this->body = $body;
    }

    public function getRoutingKey(): string
    {
        return $this->getAttribute('routing_key');
    }

    public function getContentType(): ?string
    {
        return $this->getAttribute('content_type');
    }

    public function getAppId(): ?string
    {
        return $this->getAttribute('app_id');
    }

    public function getHeaders(): array
    {
        return $this->getAttribute('headers');
    }

    public function getBodyInOriginalFormat(): mixed
    {
        return $this->body->inOriginalFormat();
    }
    
    public function getBodyAsTransported(): ?string
    {
        return $this->message->getBody();
    }

    public function getAttribute(string $attributeName): mixed
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

    private function getHeader(string $headerName): mixed
    {
        $headers = $this->getHeaders();
        if(array_key_exists($headerName, $headers))
        {
            return $headers[$headerName];
        }

        return null;
    }

    public function getAttributes(): array
    {
        return $this->message->getProperties();
    }

    public function isLastRetry($retryOccurence = \Puzzle\AMQP\Consumers\Retry::DEFAULT_RETRY_OCCURENCE): bool
    {
        $retryHeader = $this->getHeader(\Puzzle\AMQP\Consumers\Retry::DEFAULT_RETRY_HEADER);

        return ($retryHeader !== null && (int) $retryHeader >= $retryOccurence);
    }

    public function getRoutingKeyFromHeader(): ?string
    {
        return $this->getHeader('routing_key');
    }

    public function cloneIntoWritableMessage(WritableMessage $writable, bool $copyRoutingKey = false): WritableMessage
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
