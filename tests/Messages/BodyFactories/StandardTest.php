<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\BodyFactories;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\TypedBodyFactories\Json;

class StandardTest extends TestCase
{
    #[DataProvider('providerTestBuild')]
    public function testBuild($contentType, $contentAsTransported, $expected): void
    {
        $factory = new Standard();
        $body = $factory->build($contentType, $contentAsTransported);
        
        self::assertSame($expected, $body->inOriginalFormat());
    }

    public static function providerTestBuild(): array
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
                '',
            ],
        ];
    }
    
    public function testHandleContentType(): void
    {
        $factory = new Standard();
        $factory->handleContentType('application/config', new Json());
        
        $body = $factory->build('application/config', '{"pony": "Shetland"}');
        
        self::assertInstanceOf(\Puzzle\AMQP\Messages\Bodies\Json::class, $body);
        self::assertSame(['pony' => 'Shetland'], $body->inOriginalFormat());
    }
}
