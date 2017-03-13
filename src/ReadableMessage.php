<?php

namespace Puzzle\AMQP;

interface ReadableMessage extends MessageMetadata
{
    public function getRawBody();
    
    public function getDecodedBody();
    
    public function getAttributes();
    
    public function isLastRetry();
    
    public function getRoutingKeyFromHeader();
}
