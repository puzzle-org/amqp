<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\ValueObjects;

use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    private const
        UUID_1 = '30376149-a007-4a2c-a4f3-ce9ca599c81d',
        UUID_2 = 'dbf0e07c-d707-42a4-9f90-845338caa856';

    public function testValue(): void
    {
        self::assertSame(self::UUID_1, (new Uuid(self::UUID_1))->value());
    }

    public function testEquals(): void
    {
        $uuid = new Uuid(self::UUID_1);

        self::assertTrue($uuid->equals(new Uuid(self::UUID_1)));
        self::assertFalse($uuid->equals(new Uuid(self::UUID_2)));
    }

    public function testEmptyConstruct(): void
    {
        self::assertFalse((new Uuid())->equals(new Uuid()));
    }
}
