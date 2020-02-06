<?php

namespace Puzzle\AMQP;

interface MessageMetadata
{
    public const
        TRANSIENT = 1,
        PERSISTENT = 2;

    public function getRoutingKey(): string;
    
    public function getContentType(): string;

    public function getHeaders(): array;
    
    /**
     * @return mixed
     */
    public function getAttribute(string $attributeName);
}
