<?php

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
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
    
    /**
     * @dataProvider providerTestJsonEncodeException
     */
    public function testJsonEncodeException($content)
    {
        $this->expectException(\Puzzle\Pieces\Exceptions\JsonEncodeError::class);

        $body = new Json($content);
        $body->asTransported();
    }
    
    public function providerTestJsonEncodeException()
    {
        $recursionData = array();
        $recursionData[] = & $recursionData;
    
        return [
            /**
             *  Test working with phpunit ~4.8 but breaking with phpunit ~5.7 :
             *  Fatal error: Maximum function nesting level of '256' reached, aborting! in phpunit/phpunit/src/Framework/TestCase.php on line 2442
            */
            // 'JSON_ERROR_RECURSION' => [
            //     'data' => $recursionData,
            // ],
            'JSON_ERROR_INF_OR_NAN NAN' => [
                'data' => NAN,
            ],
            'JSON_ERROR_INF_OR_NAN INF' => [
                'data' => INF,
            ],
        ];
    }
    
    /**
     * @dataProvider providerTestJsonDecodeException
     */
    public function testJsonDecodeException($json)
    {
        $this->expectException(\Puzzle\Pieces\Exceptions\JsonDecodeError::class);

        $body = new Json();
        $body->changeContentWithJson($json);
    }
    
    public function providerTestJsonDecodeException()
    {
        return [
                'JSON_ERROR_STATE_MISMATCH' => [
                    '{"j": 1 ] }',
                ],
                'JSON_ERROR_CTRL_CHAR' => [
                    "\"\001 invalid json\"", # https://github.com/php/php-src/blob/6053987bc27e8dede37f437193a5cad448f99bce/ext/json/tests/bug54484.phpt#L16
                ],
                'JSON_ERROR_SYNTAX' => [
                    '{"pony":42',
                ],
                'JSON_ERROR_UTF8' => [
                    "\"\xED\xA0\xB4\""
                ],
        ];
    }
}
