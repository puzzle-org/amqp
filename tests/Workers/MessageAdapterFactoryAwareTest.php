<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\ContentType;

class FooBar
{
    use MessageAdapterFactoryAware;
}

class MessageAdapterFactoryAwareTest extends TestCase
{
    public function testFallbackConstructionWithStandardImplementation(): void
    {
        $foobar = new FooBar();
        $foobar->setMessageAdapterFactory(null);

        $message = new \Swarrot\Broker\Message('', [
            'content_type' => ContentType::EMPTY_CONTENT,
            'headers' => []
        ]);

        $adapter = $foobar->createMessageAdapter($message);

        self::assertInstanceOf(MessageAdapter::class, $adapter);
    }
}
