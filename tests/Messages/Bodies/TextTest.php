<?php

namespace Puzzle\AMQP\Messages\Bodies;

class TextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestFormat
     */
    public function testFormat($content, $expected)
    {
        $body = new Text($content);
    
        $this->assertSame($expected, $body->format());
    }
    
    public function providerTestFormat()
    {
        return [
            [array('line 1', 'line 2', 'line 3'), "line 1\nline 2\nline 3"],
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
        $body->append(['Three', 'Four']);
        $body->append('Five');
        
        $this->assertSame($expected, $body->format());
    }
    
    public function providerTestAppend()
    {
        return [
            [array('Zero', 'One'), "Zero\nOne\nTwo\nThree\nFour\nFive"],
            ['One', "One\nTwo\nThree\nFour\nFive"],
        ];
    }
}
