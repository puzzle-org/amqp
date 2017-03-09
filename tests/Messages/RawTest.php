<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\WritableMessage;

class RawTest extends \PHPUnit_Framework_TestCase
{
    use \Puzzle\Assert\ArrayRelated;
    
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

    /**
     * @dataProvider providerTestSetBody
     */
    public function testSetBody($body, $expected)
    {
        $message = new Raw('burger.over.ponies');
        $message->setBody($body);
        
        $this->assertSame($expected, $message->getFormattedBody());
    }
    
    public function providerTestSetBody()
    {
        return [
            [array('line 1', 'line 2', 'line 3'), "line 1\nline 2\nline 3"],
            ['Just a single string', 'Just a single string'],
        ];
    }
    
    public function testHeaders()
    {
        $message = new Raw('burger.over.ponies');
        
        $message->addHeader('meal', 'pizza');
        $message->addHeaders([
            'pet' => 'pony',
            'drink' => 'rum'
        ]);
        $message->addHeader('location', 'unknown');
        
        $expectedHeaders = ['meal', 'pet', 'drink', 'location', 'message_datetime'];
        $this->assertSameArrayExceptOrder(
            $expectedHeaders,
            array_keys($message->getHeaders())
        );

        $message->setAuthor($gregoire = 'Grégoire Labiche');
        $headers = $message->getHeaders();
        
        $expectedHeaders[] = 'author';
        $this->assertSameArrayExceptOrder(
            $expectedHeaders,
            array_keys($message->getHeaders())
        );
        
        $this->assertSame($gregoire, $headers['author']);
    }
    
    public function testSetExpiration()
    {
        $message = new Raw('burger.over.ponies');
        $message->setExpiration(15);
        
        $this->assertSame("15000", $message->getAttribute('expiration'));
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnknownAttribute()
    {
        $message = new Raw('burger.over.ponies');
        $message->getAttribute("Does not exist");
    }
    
    /**
     * @dataProvider providerTestBuildFromReadableMessage
     */
    public function testBuildFromReadableMessage($newRoutingKey, $expectingRoutingKey)
    {
        $readableMessage = new InMemoryJson('old.routing.key');
        $readableMessage->addHeaders([
            'h1' => 'title',
            'h2' => 'subtitle',
            'h3' => 'insignificant title',
        ]);
        $readableMessage->setAuthor($jeanPierre = 'Jean-Pierre Fortune');
        $readableMessage->setAttribute('content_encoding', $iso = 'ISO-66642-1');
        $readableMessage->setBody('This is fine');
        
        $message = Raw::buildFromReadableMessage($readableMessage, $newRoutingKey);
        
        $this->assertTrue($message instanceof WritableMessage);
        $this->assertSame($expectingRoutingKey, $message->getRoutingKey());
        
        $headers = $message->getHeaders();
        $this->assertSameArrayExceptOrder(
            ['h1', 'h2', 'h3', 'author', 'message_datetime'],
            array_keys($headers)
        );
        $this->assertSame('subtitle', $headers['h2']);
        $this->assertSame($jeanPierre, $headers['author']);
        
        $this->assertSame($iso, $message->getAttribute('content_encoding'));
    }
    
    public function providerTestBuildFromReadableMessage()
    {
        return [
            [false, 'old.routing.key'],
            ['new.routing.key', 'new.routing.key'],
        ];
    }
}