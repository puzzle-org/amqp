<?php

namespace Puzzle\AMQP;

use Puzzle\AMQP\Messages\Body;

interface WritableMessage extends MessageMetadata
{
    public function canBeDroppedSilently(): bool;
    public function disallowSilentDropping(): self;

    /**
     * @return mixed
     */
    public function getBodyInTransportFormat();

    public function setBody(Body $body): self;
    public function setExpiration(int $expirationInSeconds): self;
    public function setAuthor(string $author): self;

    public function addHeader(string $headerName, $value): self;
    public function addHeaders(array $headers): self;

    public function packAttributes(?int $timestamp = null): array;
    public function setAttribute(string $attributeName, $value): self;
    public function changeRoutingKey(string $routingKey): void;

    public function isCompressionAllowed(): bool;
    public function allowCompression(bool $allow = true): self;

    public function isChunked(): bool;
}
