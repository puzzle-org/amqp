<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\Bodies\NullBody;
use Puzzle\Pieces\ConvertibleToString;

class Message extends BodyLessMessage implements WritableMessage, ConvertibleToString
{
    use BodySetter;

    private
        $body;

    public function __construct($routingKey = '')
    {
        $this->body = new NullBody();

        parent::__construct($routingKey);
    }

    final protected function generateBodyId()
    {
        if($this->body instanceof Footprintable)
        {
            return $this->body->footprint();
        }

        return uniqid(true);
    }

    public function getContentType()
    {
        return $this->body->getContentType();
    }

    public function getBodyInTransportFormat()
    {
        return $this->body->asTransported();
    }

    public function setBody(Body $body)
    {
        $this->body = $body;
        $this->updateContentType();

        return $this;
    }

    public function packAttributes($timestamp = false)
    {
        $this->updateContentType();

        return parent::packAttributes($timestamp);
    }

    private function updateContentType()
    {
        $this->setAttribute('content_type', $this->body->getContentType());
    }

    public function __toString()
    {
        return json_encode(array(
            'routing_key' => $this->getRoutingKey(),
            'body' => (string) $this->body,
            //'attributes' => $this->attributes,
            'can be dropped silently' => $this->canBeDroppedSilently()
        ));
    }
}
