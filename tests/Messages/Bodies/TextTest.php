<?php

namespace Puzzle\AMQP\Messages\Bodies;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    #[DataProvider('providerTestToString')]
    public function testToString($expected, $content): void
    {
        $body = new Text($content);

        self::assertSame($expected, (string) $body);
    }

    public static function providerTestToString(): array
    {
        return [
            'null' => [
                'expected' => '',
                'content' => null,
            ],
            'integer' => [
                'expected' => '42',
                'content' => 42,
            ],
            'float' => [
                'expected' => '42.1337',
                'content' => 42.1337,
            ],
            'boolean' => [
                'expected' => '1',
                'content' => true,
            ],
            'object string convertible' => [
                'expected' => 'Deuteranope rex',
                'content' => new Text('Deuteranope rex'),
            ],
        ];
    }

    public function testToStringOnNonStringConvertible(): void
    {
        $this->expectException(\LogicException::class);

        $body = new Text(['burger', 'pizza']);

        (string) $body;
    }

    #[DataProvider('providerTestFormat')]
    public function testGetContentInDifferentFormats($content, $expected): void
    {
        $body = new Text($content);
    
        self::assertSame($expected, $body->asTransported());
        self::assertSame($expected, $body->inOriginalFormat());
    }

    public static function providerTestFormat(): array
    {
        return [
            ["line 1\nline 2\nline 3", "line 1\nline 2\nline 3"],
            ['Just a single string', 'Just a single string'],
        ];
    }
    
    #[DataProvider('providerTestAppend')]
    public function testAppend($content, $expected): void
    {
        $body = new Text($content);
        $body->append('Two');
        $body->append('Three', 'Four');
        $body->append('Five');
        
        self::assertSame($expected, $body->asTransported());
        self::assertSame($expected, $body->inOriginalFormat());
    }

    public static function providerTestAppend(): array
    {
        return [
            ["Zero\nOne", "Zero\nOneTwoThreeFourFive"],
            ['One', "OneTwoThreeFourFive"],
        ];
    }
    
    public function testFootprint(): void
    {
        $body = new Text("Cannot wait until the Serge Hanuque's talks !");
        
        self::assertIsString($body->footprint());
    }
}
