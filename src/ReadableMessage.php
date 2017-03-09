<?php

namespace Puzzle\AMQP;

use Puzzle\AMQP\Collections\MessageHookCollection;

interface ReadableMessage extends Message
{
    public function getRawBody();
    
    public function getDecodedBody();
    
    public function getAttributes();
    
    public function isLastRetry();
    
    public function applyHooks(MessageHookCollection $messageHookCollection);
    
    public function getRoutingKeyFromHeader();
}
