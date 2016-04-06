<?php

namespace Puzzle\AMQP;

interface Message
{
    const
        TRANSIENT = 1,
        PERSISTENT = 2;

    public function getFlags();

    public function getRoutingKey();
    public function getContentType();
    public function getAppId();
    public function getHeaders();
    public function getAttribute($attributeName);
}