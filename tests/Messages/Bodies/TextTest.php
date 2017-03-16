<?php

namespace Puzzle\AMQP\Messages\Bodies;

class TextTest extends \PHPUnit_Framework_TestCase
{
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
