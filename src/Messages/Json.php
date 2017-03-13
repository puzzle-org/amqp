<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\WritableMessage;

class Json extends Raw implements WritableMessage
{
    protected function formatBody()
    {
        return json_encode($this->body);
    }

    public function getContentType()
    {
        return ContentType::JSON;
    }
    
    public function setBodyWithJson($json)
    {
        $this->body = json_decode($json, true);
    
        return $this;
    }
}
