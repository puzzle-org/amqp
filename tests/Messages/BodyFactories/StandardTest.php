<?php

namespace Puzzle\AMQP\Messages\BodyFactories;

use Puzzle\AMQP\Messages\ContentType;

class StandardTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestBuild
     */
    public function testBuild($contentType, $contentAsTransported, $expected)
    {
        $factory = new Standard();
        $body = $factory->build($contentType, $contentAsTransported);
        
        $this->assertSame($expected, $body->inOriginalFormat());
    }
    
    public function providerTestBuild()
    {
        return [
            [
                ContentType::TEXT,
                'Deuteranopus Rex over colored ponies',
                'Deuteranopus Rex over colored ponies',
            ],
            [
                ContentType::JSON,
                '{"message": "Deuteranopus Rex over colored ponies"}',
                ['message' => 'Deuteranopus Rex over colored ponies'],
            ],
            [
                ContentType::BINARY,
                '00000000000000000000',
                '00000000000000000000',
            ],
            [
                'unknown/type',
                'Deunope or Trinope ?',
                null,
            ],
        ];
    }
}
