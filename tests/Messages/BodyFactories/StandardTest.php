<?php

namespace Puzzle\AMQP\Messages\BodyFactories;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\TypedBodyFactories\Json;

class StandardTest extends TestCase
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
    
    public function testHandleContentType()
    {
        $factory = new Standard();
        $factory->handleContentType('application/config', new Json());
        
        $body = $factory->build('application/config', '{"pony": "Shetland"}');
        
        $this->assertTrue($body instanceof \Puzzle\AMQP\Messages\Bodies\Json);
        $this->assertSame(['pony' => 'Shetland'], $body->inOriginalFormat());
    }
}
