<?php

namespace Puzzle\AMQP\Messages\Bodies;

class JsonTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContentInDifferentFormats()
    {
        $content = array(
            'planche' => 'gourdin',
            'burgers' => array(
                'Mc Pony Deluxe', 'Fat Julian', 'Double Fat Vendean Edition'
            ),
        );

        $body = new Json($content);

        $this->assertSame($content, json_decode($body->asTransported(), true), 'Body must be encoded in json');
        
        $body->changeContent('yolo');
        $this->assertSame(['yolo'], json_decode($body->asTransported(), true), 'Body must be encoded in json');
        $this->assertSame(['yolo'], $body->inOriginalFormat());
    }

    public function testSetBodyWithJson()
    {
        $content = json_encode(['burger' => 'big pony']);

        $body = new Json();
        $body->changeContentWithJson($content);

        $this->assertSame($content, $body->asTransported(), 'Body must not be encoded twice');
    }
}
