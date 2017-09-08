<?php

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\Messages\ContentType;

class FooBar
{
    use MessageAdapterFactoryAware;
}

class MessageAdapterFactoryAwareTest extends \PHPUnit_Framework_TestCase
{
    public function testFallbackConstructionWithStandardImplementation()
    {
        $foobar = new FooBar();
        $foobar->setMessageAdapterFactory(null);

        $message = new \Swarrot\Broker\Message('', [
            'content_type' => ContentType::EMPTY_CONTENT,
            'headers' => []
        ]);

        $adapter = $foobar->createMessageAdapter($message);

        $this->assertTrue($adapter instanceof MessageAdapter);
    }
}
