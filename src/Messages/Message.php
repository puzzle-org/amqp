<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages;

use Psr\Log\InvalidArgumentException;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\Bodies\EmptyBody;
use Puzzle\Pieces\ConvertibleToString;

class Message implements WritableMessage, ConvertibleToString
{
    const string
        ATTRIBUTE_CONTENT_TYPE = 'content_type';

    use BodySetter;

    private Body
        $body;
    private bool
        $canBeDroppedSilently,
        $allowCompression;
    private ?string
        $userContentType;
    private array
        $headers,
        $attributes;

    public function __construct(string $routingKey = '')
    {
        $this->body = new EmptyBody();

        $this->canBeDroppedSilently = true;
        $this->allowCompression = false;
        $this->userContentType = null;
        $this->headers = [];
        $this->initializeAttributes();

        $this->changeRoutingKey($routingKey);
    }

    public function changeRoutingKey(string $routingKey): void
    {
        $this->setAttribute('routing_key', $routingKey);
    }

    private function initializeAttributes(): void
    {
        $this->attributes = [
            'routing_key' => null,
            self::ATTRIBUTE_CONTENT_TYPE=> $this->getContentType(),
            'content_encoding' => 'utf8',
            'message_id' => function($timestamp) {
                return sha1($this->getRoutingKey() . $timestamp . $this->generateBodyId() . mt_rand());
            },
            'user_id' => null,
            'app_id' => null,
            'delivery_mode' => self::PERSISTENT,
            'priority' => null,
            'timestamp' => function($timestamp) {
                return $timestamp;
            },
            'expiration' => null,
            'type' => null,
            'reply_to' => null,
            'correlation_id' => null,
            'headers' => function(int $timestamp) {
                return $this->packHeaders($timestamp);
            },
        ];
    }

    private function generateBodyId(): string
    {
        if($this->body instanceof Footprintable)
        {
            return $this->body->footprint();
        }

        return uniqid('', true);
    }

    public function canBeDroppedSilently(): bool
    {
        return $this->canBeDroppedSilently;
    }

    public function disallowSilentDropping(): static
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
        return $this->getAttribute('routing_key');
    }

    public function getBodyInTransportFormat(): string|\Generator
    {
        return $this->body->asTransported();
    }

    public function setBody(Body $body): static
    {
        $this->body = $body;
        $this->updateContentType();

        return $this;
    }

    private function updateContentType(): void
    {
        $this->attributes[self::ATTRIBUTE_CONTENT_TYPE] = $this->getContentType();
    }

    public function addHeader(string $headerName, mixed $value): static
    {
        $this->headers[$headerName] = $value;

        return $this;
    }

    public function addHeaders(array $headers): static
    {
        foreach($headers as $name => $value)
        {
            $this->addHeader($name, $value);
        }

        return $this;
    }

    public function setAuthor(string $author): static
    {
        $this->addHeader('author', $author);

        return $this;
    }

    public function packAttributes(int|bool $timestamp = false): array
    {
        $this->updateContentType();

        if($timestamp === false)
        {
            $timestamp = (new \DateTime("now"))->getTimestamp();
        }

        return array_map(static function($value) use($timestamp) {

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

    public function setAttribute(string $attributeName, mixed $value): static
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

    public function getAttribute(string $attributeName): mixed
    {
        if(array_key_exists($attributeName, $this->attributes))
        {
            return $this->attributes[$attributeName];
        }

        throw new InvalidArgumentException(sprintf('Property "%s" is unknown or is not a message property', $attributeName));
    }

    public function __toString(): string
    {
        return json_encode(array(
            'routing_key' => $this->getRoutingKey(),
            'body' => (string) $this->body,
            'attributes' => $this->attributes,
            'can be dropped silently' => $this->canBeDroppedSilently
        ));
    }

    public function setExpiration(int $expirationInSeconds): static
    {
        $ttlInMs = 1000 * $expirationInSeconds;

        $this->setAttribute('expiration', (string) $ttlInMs);

        return $this;
    }

    public function isCompressionAllowed(): bool
    {
        return $this->allowCompression;
    }

    public function allowCompression(bool $allow = true): static
    {
        $this->allowCompression = $allow;

        return $this;
    }

    public function isChunked(): bool
    {
        return $this->body->isChunked();
    }
}
