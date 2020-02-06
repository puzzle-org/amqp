<?php

namespace Puzzle\AMQP;

use Puzzle\AMQP\Consumers\Retry;

interface ReadableMessage extends MessageMetadata
{
    /**
     * @return mixed
     */
    public function getBodyInOriginalFormat();
    
    /**
     * @return mixed
     */
    public function getBodyAsTransported();
    public function getAppId(): string;
    public function getAttributes(): array;
    public function isLastRetry(int $retryOccurence = Retry::DEFAULT_RETRY_OCCURENCE): bool;
    public function getRoutingKeyFromHeader(): ?string;

    public function cloneIntoWritableMessage(WritableMessage $writable, bool $copyRoutingKey = false): WritableMessage;
}
