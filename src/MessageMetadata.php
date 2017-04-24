<?php

namespace Puzzle\AMQP;

interface MessageMetadata
{
    const
        TRANSIENT = 1,
        PERSISTENT = 2;

    /**
     * @return string
     */
    public function getRoutingKey();
    
    /**
     * @return string
     */
    public function getContentType();

    /**
     * @return string
     */
    public function getTransportContentType();

    /**
     * @return array
     */
    public function getHeaders();
    
    /**
     * @return mixed
     */
    public function getAttribute($attributeName);
}
