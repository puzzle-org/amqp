<?php

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;

class NullBodyTest extends TestCase
{
    public function testNullBody(): void
    {
        $body = new EmptyBody();
        
        self::assertSame('', $body->inOriginalFormat());
        self::assertSame('', $body->asTransported());
        self::assertEmpty((string) $body);
    }
}
