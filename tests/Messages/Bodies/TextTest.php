<?php

namespace Puzzle\AMQP\Messages\Bodies;

class TextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestToString
     */
    public function testToString($expected, $content)
    {
        $body = new Text($content);

        $this->assertSame($expected, (string) $body);
    }

    public function providerTestToString()
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

    /**
     * @expectedException \LogicException
     */
    public function testToStringOnNonStringConvertible()
    {
        $body = new Text(['burger', 'pizza']);

        (string) $body;
    }

    /**
     * @dataProvider providerTestFormat
     */
    public function testGetContentInDifferentFormats($content, $expected)
    {
        $body = new Text($content);
    
        $this->assertSame($expected, $body->asTransported());
        $this->assertSame($expected, $body->inOriginalFormat());
    }
    
    public function providerTestFormat()
    {
        return [
            ["line 1\nline 2\nline 3", "line 1\nline 2\nline 3"],
            ['Just a single string', 'Just a single string'],
        ];
    }
    
    /**
     * @dataProvider providerTestAppend
     */
    public function testAppend($content, $expected)
    {
        $body = new Text($content);
        $body->append('Two');
        $body->append('Three', 'Four');
        $body->append('Five');
        
        $this->assertSame($expected, $body->asTransported());
        $this->assertSame($expected, $body->inOriginalFormat());
    }
    
    public function providerTestAppend()
    {
        return [
            ["Zero\nOne", "Zero\nOneTwoThreeFourFive"],
            ['One', "OneTwoThreeFourFive"],
        ];
    }
    
    public function testFootprint()
    {
        $body = new Text("Cannot wait until the Serge Hanuque's talks !");
        
        $this->assertInternalType('string', $body->footprint());
    }
}
