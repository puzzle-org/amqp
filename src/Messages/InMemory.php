<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\Messages\Bodies\EmptyBody;
use Puzzle\AMQP\Workers\MessageAdapterFactory;

class InMemory
{
    /**
     * @return \Puzzle\AMQP\ReadableMessage
     */
    public static function build($routingKey, ?Body $body = null, array $additionalHeaders = [], array $additionalAttributes = [])
    {
        if(! $body instanceof Body)
        {
            $body = new EmptyBody();
        }

        $attributes = array_merge([
            'content_type' => $body->getContentType(),
            'routing_key' => $routingKey,
            'content_encoding' => 'utf8',
            'message_id' => uniqid(true),
        ], $additionalAttributes);

        $attributes['headers'] = array_merge([
            'routing_key' => $routingKey,
            'app_id' => 'memory',
            'message_datetime' => date('Y-m-d H:i:s'),
        ], $additionalHeaders);

        $factory = new MessageAdapterFactory();
        
        return $factory->build(new \Swarrot\Broker\Message($body->asTransported(), $attributes));
    }
}
