<?php

namespace Puzzle\AMQP;

interface ReadableMessage extends MessageMetadata
{
    /**
     * @return \Puzzle\AMQP\Messages\Body
     */
    public function getBody();

    /**
     * @return mixed
     */
    public function getBodyInOriginalFormat();
    
    /**
     * @return array
     */
    public function getAttributes();
    
    /**
     * @return boolean
     */
    public function isLastRetry();
    
    /**
     *  @return string
     */
    public function getRoutingKeyFromHeader();
}
