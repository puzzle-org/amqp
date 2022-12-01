<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\ValueObjects;

final class Uuid
{
    private string
        $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value ?? (string) \Ramsey\Uuid\Uuid::uuid4();
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $uuid): bool
    {
        return $uuid->value === $this->value;
    }
}
