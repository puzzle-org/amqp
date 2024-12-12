<?php

namespace Puzzle\AMQP;

use Puzzle\AMQP\Messages\Body;

interface WritableMessage extends MessageMetadata
{
    public function canBeDroppedSilently(): bool;

    public function disallowSilentDropping(): static;

    public function getBodyInTransportFormat(): mixed;

    public function setBody(Body $body): static;

    public function setExpiration(int $expirationInSeconds): static;

    public function addHeader(string $headerName, mixed $value): static;

    public function addHeaders(array $headers): static;

    public function setAuthor(string $author): static;

    public function packAttributes(int|bool $timestamp = false): array;

    public function setAttribute(string $attributeName, mixed $value): static;

    public function changeRoutingKey(string $routingKey): void;

    public function isCompressionAllowed(): bool;

    public function allowCompression(bool $allow = true): static;

    public function isChunked(): bool;
}
