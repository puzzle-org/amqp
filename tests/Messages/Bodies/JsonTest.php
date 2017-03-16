<?php

namespace Puzzle\AMQP\Messages\Bodies;

class JsonTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFormattedBody()
    {
        $content = array(
            'planche' => 'gourdin',
            'burgers' => array(
                'Mc Pony Deluxe', 'Fat Julian', 'Double Fat Vendean Edition'
            ),
        );

        $body = new Json($content);

        $this->assertSame($content, json_decode($body->format(), true), 'Body must be encoded in json');
        
        $body->changeContent('yolo');
        $this->assertSame(['yolo'], json_decode($body->format(), true), 'Body must be encoded in json');
    }

    public function testSetBodyWithJson()
    {
        $content = json_encode(['burger' => 'big pony']);

        $body = new Json();
        $body->changeContentWithJson($content);

        $this->assertSame($content, $body->format(), 'Body must not be encoded twice');
    }
}
