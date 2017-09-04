<?php

namespace Puzzle\AMQP\Messages;

use Psr\Log\InvalidArgumentException;
use Puzzle\Pieces\ConvertibleToString;
use Puzzle\AMQP\MessageMetadata;

abstract class BodyLessMessage implements MessageMetadata, ConvertibleToString
{
    private
        $canBeDroppedSilently,
        $allowCompression,
        $headers,
        $attributes;

    public function __construct($routingKey = '')
    {
        $this->canBeDroppedSilently = true;
        $this->allowCompression = false;

        $this->headers = array();
        $this->initializeAttributes();

        $this->changeRoutingKey($routingKey);
    }

    abstract public function getContentType();

    public function changeRoutingKey($routingKey)
    {
        $this->setAttribute('routing_key', $routingKey);
    }

    private function initializeAttributes()
    {
        $this->attributes = array(
            'routing_key' => null,
            'content_type' => $this->getContentType(),
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
            'headers' => function($timestamp) {
                return $this->packHeaders($timestamp);
            },
        );
    }

    protected function generateBodyId()
    {
        return uniqid(true);
    }

    public function canBeDroppedSilently()
    {
        return $this->canBeDroppedSilently;
    }

    public function disallowSilentDropping()
    {
        $this->canBeDroppedSilently = false;


        return $this;
    }

    public function getRoutingKey()
    {
        return $this->getAttribute('routing_key');
    }

    public function addHeader($headerName, $value)
    {
        $this->headers[$headerName] = $value;

        return $this;
    }

    public function addHeaders(array $headers)
    {
        foreach($headers as $name => $value)
        {
            $this->addHeader($name, $value);
        }

        return $this;
    }

    public function setAuthor($author)
    {
        $this->addHeader('author', $author);

        return $this;
    }

    public function packAttributes($timestamp = false)
    {
        if($timestamp === false)
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

    private function packHeaders($timestamp)
    {
        $this->headers['message_datetime'] = date('Y-m-d H:i:s', $timestamp);

        return $this->headers;
    }

    public function setAttribute($attributeName, $value)
    {
        if($attributeName !== 'headers')
        {
            if(array_key_exists($attributeName, $this->attributes))
            {
                $this->attributes[$attributeName] = $value;
            }
        }

        return $this;
    }

    public function getHeaders()
    {
        $attributes = $this->packAttributes();

        return $attributes['headers'];
    }

    public function getAttribute($attributeName)
    {
        if(array_key_exists($attributeName, $this->attributes))
        {
            return $this->attributes[$attributeName];
        }

        throw new InvalidArgumentException(sprintf('Property "%s" is unknown or is not a message property', $attributeName));
    }

    public function __toString()
    {
        return json_encode(array(
            'routing_key' => $this->getRoutingKey(),
            'attributes' => $this->attributes,
            'can be dropped silently' => $this->canBeDroppedSilently
        ));
    }

    public function setExpiration($expirationInSeconds)
    {
        $ttlInMs = 1000 * (int) $expirationInSeconds;

        $this->setAttribute('expiration', (string) $ttlInMs);

        return $this;
    }

    public function isCompressionAllowed()
    {
        return $this->allowCompression;
    }

    public function allowCompression($allow = true)
    {
        $this->allowCompression = $allow;

        return $this;
    }
}
