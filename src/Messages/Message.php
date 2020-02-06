<?php

namespace Puzzle\AMQP\Messages;

use Psr\Log\InvalidArgumentException;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\Bodies\NullBody;
use Puzzle\Pieces\ConvertibleToString;
use Puzzle\Pieces\Exceptions\JsonEncodeError;
use Puzzle\Pieces\Json;

class Message implements WritableMessage, ConvertibleToString
{
    public const
        ATTRIBUTE_CONTENT_TYPE = 'content_type';

    use BodySetter;

    private
        $body,
        $canBeDroppedSilently,
        $allowCompression,
        $userContentType,
        $headers,
        $attributes;

    public function __construct(string $routingKey = '')
    {
        $this->body = new NullBody();

        $this->canBeDroppedSilently = true;
        $this->allowCompression = false;
        $this->userContentType = null;
        $this->headers = array();
        $this->initializeAttributes();

        $this->changeRoutingKey($routingKey);
    }

    public function changeRoutingKey(string $routingKey): void
    {
        $this->setAttribute('routing_key', $routingKey);
    }

    private function initializeAttributes(): void
    {
        $this->attributes = array(
            'routing_key' => null,
            self::ATTRIBUTE_CONTENT_TYPE=> $this->getContentType(),
            'content_encoding' => 'utf8',
            'message_id' => function(int $timestamp) {
                return sha1($this->getRoutingKey() . $timestamp . $this->generateBodyId() . mt_rand());
            },
            'user_id' => null,
            'app_id' => null,
            'delivery_mode' => self::PERSISTENT,
            'priority' => null,
            'timestamp' => function(int $timestamp) {
                return $timestamp;
            },
            'expiration' => null,
            'type' => null,
            'reply_to' => null,
            'correlation_id' => null,
            'headers' => function(int $timestamp) {
                return $this->packHeaders($timestamp);
            },
        );
    }

    private function generateBodyId(): string
    {
        if($this->body instanceof Footprintable)
        {
            return $this->body->footprint();
        }

        return uniqid(true);
    }

    public function canBeDroppedSilently(): bool
    {
        return $this->canBeDroppedSilently;
    }

    public function disallowSilentDropping(): WritableMessage
    {
        $this->canBeDroppedSilently = false;

        return $this;
    }

    public function getContentType(): string
    {
        if($this->userContentType === null)
        {
            return $this->body->getContentType();
        }

        return $this->userContentType;
    }

    public function getRoutingKey(): string
    {
        return (string) $this->getAttribute('routing_key');
    }

    public function getBodyInTransportFormat()
    {
        return $this->body->asTransported();
    }

    public function setBody(Body $body): WritableMessage
    {
        $this->body = $body;
        $this->updateContentType();

        return $this;
    }

    private function updateContentType(): void
    {
        $this->attributes[self::ATTRIBUTE_CONTENT_TYPE] = $this->getContentType();
    }

    public function addHeader(string $headerName, $value): WritableMessage
    {
        $this->headers[$headerName] = $value;

        return $this;
    }

    public function addHeaders(array $headers): WritableMessage
    {
        foreach($headers as $name => $value)
        {
            $this->addHeader($name, $value);
        }

        return $this;
    }

    public function setAuthor(string $author): WritableMessage
    {
        $this->addHeader('author', $author);

        return $this;
    }

    public function packAttributes(?int $timestamp = null): array
    {
        $this->updateContentType();

        if($timestamp === null)
        {
            $timestamp = (new \DateTime("now"))->getTimestamp();
        }

        return array_map(function($value) use($timestamp) {

            if($value instanceof \Closure)
            {
                $value = $value($timestamp);
            }

            return $value;

        }, $this->attributes);
    }

    private function packHeaders(int $timestamp): array
    {
        $this->headers['message_datetime'] = date('Y-m-d H:i:s', $timestamp);

        return $this->headers;
    }

    public function setAttribute(string $attributeName, $value): WritableMessage
    {
        if($attributeName !== 'headers')
        {
            if(array_key_exists($attributeName, $this->attributes))
            {
                $this->attributes[$attributeName] = $value;

                if($attributeName === self::ATTRIBUTE_CONTENT_TYPE)
                {
                    $this->userContentType = $value;
                }
            }
        }

        return $this;
    }

    public function getHeaders(): array
    {
        $attributes = $this->packAttributes();

        return $attributes['headers'];
    }

    public function getAttribute(string $attributeName)
    {
        if(array_key_exists($attributeName, $this->attributes))
        {
            return $this->attributes[$attributeName];
        }

        throw new InvalidArgumentException(sprintf('Property "%s" is unknown or is not a message property', $attributeName));
    }

    public function __toString(): string
    {
        try {
            return (string) Json::encode([
                'routing_key' => $this->getRoutingKey(),
                'body' => (string) $this->body,
                'attributes' => $this->attributes,
                'can be dropped silently' => $this->canBeDroppedSilently
            ]);
        }
        catch(JsonEncodeError $e)
        {
            return sprintf('Can\'t json encode the message. error: "%s"', $e->getMessage());
        }
    }

    public function setExpiration(int $expirationInSeconds): WritableMessage
    {
        $ttlInMs = 1000 * (int) $expirationInSeconds;

        $this->setAttribute('expiration', (string) $ttlInMs);

        return $this;
    }

    public function isCompressionAllowed(): bool
    {
        return $this->allowCompression;
    }

    public function allowCompression(bool $allow = true): WritableMessage
    {
        $this->allowCompression = (bool) $allow;

        return $this;
    }

    public function isChunked(): bool
    {
        return $this->body->isChunked();
    }
}
