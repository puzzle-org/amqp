<?php

namespace Puzzle\AMQP\Messages;

class RawTest extends \PHPUnit_Framework_TestCase
{
    public function testPackAttributes()
    {
        $msg = new Raw('pony.black_unicorn');
        $msg->setBody(array('burger' => 'Mc Julian Deluxe'));

        $t = 42;

        $attributes1 = $msg->packAttributes($t);
        $attributes2 = $msg->packAttributes($t);
        $this->assertSame($t, $attributes2['timestamp']);

        // Timestamp has changed => id must be different
        $t = 43;

        $attributes3 = $msg->packAttributes($t);
        $this->assertNotSame($attributes2['timestamp'], $attributes3['timestamp'], 'Timestamp has changed, message_id must be recomputed');
        $this->assertSame($t, $attributes3['timestamp'], 'Timestamp must be actualized');
        $this->assertNotSame(
            $attributes2['message_id'],
            $attributes3['message_id'],
            'Timestamp has changed, message_id must be recomputed'
        );

        // Body has changed => id must be different
        $msg->setBody(array('pizza' => 'Julianita'));

        $this->assertNotSame(
            $attributes2['message_id'],
            $attributes3['message_id'],
            'Body has changed, message_id must be recomputed'
        );
    }

    public function testPackAttributesWithCustomHeaders()
    {
        $msg = new Raw('burger.french_fries');
        $msg->addHeader('X-Planche', 'gourdin')
            ->addHeader('X-Version', '1.0');

        $attributes = $msg->packAttributes($epoch = 0);

        $this->assertArrayHasKey('headers', $attributes);

        $headers = $attributes['headers'];

        $this->assertArrayHasKey('message_datetime', $headers);
        $this->assertSame($epoch, strtotime($headers['message_datetime']));

        $this->assertArrayHasKey('X-Planche', $headers);
        $this->assertSame('gourdin', $headers['X-Planche']);

        $this->assertArrayHasKey('X-Version', $headers);
        $this->assertSame('1.0', $headers['X-Version']);
    }
    
    /**
     * @dataProvider providerTestSetAttribute
     */
    public function testSetAttribute($attributeName, $expectFind, $expectModification)
    {
        $newValue = 'Dark sysadmin';
        
        $message = new Raw('burger.over.ponies');
        $message->setAttribute($attributeName, $newValue);
        
        $attributes = $message->packAttributes();
        $this->assertSame($expectFind, array_key_exists($attributeName, $attributes));
        
        if($expectFind === true)
        {
            $this->assertSame($expectModification, $attributes[$attributeName] === $newValue);
        }
    }
    
    public function providerTestSetAttribute()
    {
        return array(
            array('app_id', true, true),
            array('timestamp', true, true),
            array('headers', true, false),
            array('big_pony', false, false),
            array('appid', false, false),
        );
    }
}