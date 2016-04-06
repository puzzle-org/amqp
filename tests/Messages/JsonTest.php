<?php

namespace Puzzle\AMQP\Messages;

class JsonTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFormattedBody()
    {
        $body = array(
            'planche' => 'gourdin',
            'burgers' => array(
                'Mc Pony Deluxe', 'Fat Julian', 'Double Fat Vendean Edition'
            ),
        );

        $message = new Json();
        $message->setBody($body);

        $this->assertSame($body, json_decode($message->getFormattedBody(), true), 'Body must be encoded in json');
    }

    public function testSetBodyWithJson()
    {
        $body = json_encode(['burger' => 'big pony']);

        $message = new Json();
        $message->setBodyWithJson($body);

        $this->assertSame($body, $message->getFormattedBody(), 'Body must not be encoded twice');
    }
}