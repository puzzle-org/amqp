<?php

namespace Puzzle\AMQP\Messages;

use Psr\Log\InvalidArgumentException;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\Bodies\NullBody;

class Message implements WritableMessage
{
    use BodySetter;
    
    protected
        $body;
    
    private
        $flags,
        $headers,
        $attributes;

    public function __construct($routingKey = '')
    {
        $this->body = new NullBody();
        $this->flags = AMQP_NOPARAM;
        $this->headers = array();
        $this->initializeAttributes();
        $this->setAttribute('routing_key', $routingKey);
    }
    
    private function initializeAttributes()
    {
        $this->attributes = array(
            'routing_key' => null,
            'content_type' => $this->getContentType(),
            'content_encoding' => 'utf8',
            'message_id' => function($timestamp) {
                return sha1($this->getRoutingKey() . $timestamp . $this->body->footprint() . mt_rand());
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

    public function getContentType()
    {
        return $this->body->getContentType();
    }

    public function getRoutingKey()
    {
        return $this->getAttribute('routing_key');
    }

    public function getFormattedBody()
    {
        return $this->body->format();
    }

    public function setBody(Body $body)
    {
        $this->body = $body;
        $this->updateContentType();
        
        return $this;
    }
    
    private function updateContentType()
    {
        $this->attributes['content_type'] = $this->body->getContentType();
    }
    
    public function getFlags()
    {
        return $this->flags;
    }

    public function setFlags($flags)
    {
        $this->flags = $flags;

        return $this;
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
        $this->updateContentType();
        
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

    public function getAppId()
    {
        return $this->getAttribute('app_id');
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
            'body' => (string) $this->body,
            'attributes' => $this->attributes,
            'flags' => $this->flags
        ));
    }

    public function setExpiration($expirationInSeconds)
    {
        $ttlInMs = 1000 * (int) $expirationInSeconds;

        $this->setAttribute('expiration', (string) $ttlInMs);

        return $this;
    }

    public static function buildFromReadableMessage(ReadableMessage $readableMessage, $newRoutingKey = false)
    {
        $routingKey = $readableMessage->getRoutingKey();

        if($newRoutingKey !== false)
        {
            $routingKey = $newRoutingKey;
        }

        $writableMessage = new static($routingKey);
        $writableMessage->setBody($readableMessage->getBody());

        $writableMessage->addHeaders($readableMessage->getHeaders());

        $attributes = $readableMessage->getAttributes();
        $skippedAttributes = array('timestamp', 'headers', 'app_id', 'routing_key');
        foreach($attributes as $attributeName => $value)
        {
            if(! in_array($attributeName, $skippedAttributes))
            {
                $writableMessage->setAttribute($attributeName, $value);
            }
        }

        return $writableMessage;
    }
}
