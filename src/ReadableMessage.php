<?php

declare(strict_types = 1);

namespace Puzzle\AMQP;

interface ReadableMessage extends MessageMetadata
{
    public function getBodyInOriginalFormat(): mixed;
    
    public function getBodyAsTransported(): mixed;

    public function getAppId(): string;

    public function getAttributes(): array;
    
    public function isLastRetry(): bool;
    
    public function getRoutingKeyFromHeader(): ?string;

    public function cloneIntoWritableMessage(WritableMessage $writable, bool $copyRoutingKey = false): WritableMessage;
}
