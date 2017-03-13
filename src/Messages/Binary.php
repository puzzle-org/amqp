<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\WritableMessage;

class Binary extends Raw implements WritableMessage
{
    protected function initBody()
    {
        $this->body = null;
    }
    
    protected function formatBody()
    {
        return $this->body;
    }
    
    public function setBody($body)
    {
        $this->body = $body;
    
        return $this;
    }
    
    public function getContentType()
    {
        return ContentType::BINARY;
    }
    
    public function __toString()
    {
        return json_encode(array(
            'routing_key' => $this->getRoutingKey(),
            'body' => sprintf('<binary stream of %d bytes>', strlen($this->body)),
            'attributes' => $this->attributes,
            'flags' => $this->flags
        ));
    }
}
