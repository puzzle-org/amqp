<?php

namespace Puzzle\AMQP;

interface MessageMetadata
{
    const int
        TRANSIENT = 1,
        PERSISTENT = 2;

    public function getRoutingKey(): string;
    
    public function getContentType(): string;

    public function getHeaders(): array;
    
    public function getAttribute(string $attributeName): mixed;
}
