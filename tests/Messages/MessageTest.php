<?php

namespace Puzzle\AMQP\Messages;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\MessageMetadata;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;

class MessageTest extends TestCase
{
    use \Puzzle\Assert\ArrayRelated;

    public function testGetBodyInTransportFormat()
    {
        $msg = new Message('routing.key');
        $msg->setJson($json = ['Fabrice' => 'Auzamandes']);

        $this->assertSame(json_encode($json), $msg->getBodyInTransportFormat());
    }

    public function testPackAttributes()
    {
        $msg = new Message('pony.black_unicorn');
        $msg->setJson(array('burger' => 'Mc Julian Deluxe'));

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
        $msg->setText('deuteranope');

        $this->assertNotSame(
            $attributes2['message_id'],
            $attributes3['message_id'],
            'Body has changed, message_id must be recomputed'
        );
    }

    public function testPackAttributesWithCustomHeaders()
    {
        $msg = new Message('burger.french_fries');
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

        $message = new Message('burger.over.ponies');
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

    public function testHeaders()
    {
        $message = new Message('burger.over.ponies');

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
        $message = new Message('burger.over.ponies');
        $message->setExpiration(15);

        $this->assertSame("15000", $message->getAttribute('expiration'));
    }

    public function testUnknownAttribute()
    {
        $this->expectException(\InvalidArgumentException::class);

        $message = new Message('burger.over.ponies');
        $message->getAttribute("Does not exist");
    }

    public function testSilentDropping()
    {
        $msg = new Message('my.key');

        $this->assertTrue($msg->canBeDroppedSilently());

        $msg->disallowSilentDropping();
        $this->assertFalse($msg->canBeDroppedSilently());
    }

    public function testAllowCompression()
    {
        $msg = new Message('my.key');

        $this->assertFalse($msg->isCompressionAllowed());
        $msg->allowCompression();
        $this->assertTrue($msg->isCompressionAllowed());
    }

    public function testChangingContentType()
    {
        $msg = new Message('my.key');
        $this->assertContentTypeIs(ContentType::EMPTY_CONTENT, $msg);

        $msg->setText('unicorn');
        $this->assertContentTypeIs(ContentType::TEXT, $msg);

        $msg->setJson([]);
        $this->assertContentTypeIs(ContentType::JSON, $msg);

        $msg->packAttributes();
        $this->assertContentTypeIs(ContentType::JSON, $msg);

        $msg->setStreamedBinary('pony', new ChunkSize(1));
        $this->assertContentTypeIs(ContentType::BINARY, $msg);

        $msg->setText('pony');
        $this->assertContentTypeIs(ContentType::TEXT, $msg);

        $msg->setAttribute(Message::ATTRIBUTE_CONTENT_TYPE, 'application/xml');
        $this->assertContentTypeIs('application/xml', $msg);

        $msg->setJson([]);
        $this->assertContentTypeIs('application/xml', $msg);

        $msg->packAttributes();
        $this->assertContentTypeIs('application/xml', $msg);

        $msg->setAttribute(Message::ATTRIBUTE_CONTENT_TYPE, 'X-pony');
        $this->assertContentTypeIs('X-pony', $msg);

        $msg->setJson([]);
        $this->assertContentTypeIs('X-pony', $msg);

        $msg->packAttributes();
        $this->assertContentTypeIs('X-pony', $msg);
    }

    private function assertContentTypeIs($contentType, MessageMetadata $message)
    {
        $this->assertSame($contentType, $message->getContentType());
    }

    /**
     * @dataProvider providerTestIsChunked
     */
    public function testIsChunked(Message $message, $expected)
    {
        $this->assertSame($expected, $message->isChunked());
    }

    public function providerTestIsChunked()
    {
        return [
            [(new Message()), false],
            [(new Message())->setJson([]), false],
            [(new Message())->setText(''), false],
            [(new Message())->setBinary(''), false],
            [(new Message())->setStreamedBinary('', new ChunkSize('1M')), true],
            [(new Message())->setStreamedFile(__FILE__, new ChunkSize('1M')), true],
            [(new Message())->setStreamedFile(__FILE__), false],
        ];
    }

}
