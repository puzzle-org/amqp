<?php

namespace Puzzle\AMQP\Collections;

use Puzzle\AMQP\MessageHook;

class PeterPan implements MessageHook
{
    public function process(array $body) {}
}
class Clochette implements MessageHook
{
    public function process(array $body) {}
}

class MessageHookCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testOnlyHoldMessageHook()
    {
        $collection = new MessageHookCollection([
            new PeterPan(),
            new \stdClass(),
            new PeterPan(),
            new Clochette(),
            array(),
            new PeterPan(),
            false,
            new Clochette(),
        ]);

        $collection->add(new PeterPan());

        $this->assertContainsOnlyInstancesOf('\Puzzle\AMQP\MessageHook', $collection->getIterator());
    }
}
