<?php

namespace Puzzle\AMQP\Messages;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\MessageMetadata;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;

class MessageTest extends TestCase
{
    use \Puzzle\Assert\ArrayRelated;

    public function testGetBodyInTransportFormat(): void
    {
        $msg = new Message('routing.key');
        $msg->setJson($json = ['Fabrice' => 'Auzamandes']);

        self::assertSame(json_encode($json), $msg->getBodyInTransportFormat());
    }

    public function testPackAttributes(): void
    {
        $msg = new Message('pony.black_unicorn');
        $msg->setJson(array('burger' => 'Mc Julian Deluxe'));

        $t = 42;

        $attributes1 = $msg->packAttributes($t);
        $attributes2 = $msg->packAttributes($t);
        self::assertSame($t, $attributes2['timestamp']);

        // Timestamp has changed => id must be different
        $t = 43;

        $attributes3 = $msg->packAttributes($t);
        self::assertNotSame($attributes2['timestamp'], $attributes3['timestamp'], 'Timestamp has changed, message_id must be recomputed');
        self::assertSame($t, $attributes3['timestamp'], 'Timestamp must be actualized');
        self::assertNotSame(
            $attributes2['message_id'],
            $attributes3['message_id'],
            'Timestamp has changed, message_id must be recomputed'
        );

        // Body has changed => id must be different
        $msg->setText('deuteranope');

        self::assertNotSame(
            $attributes2['message_id'],
            $attributes3['message_id'],
            'Body has changed, message_id must be recomputed'
        );
    }

    public function testPackAttributesWithCustomHeaders(): void
    {
        $msg = new Message('burger.french_fries');
        $msg->addHeader('X-Planche', 'gourdin')
            ->addHeader('X-Version', '1.0');

        $attributes = $msg->packAttributes($epoch = 0);

        self::assertArrayHasKey('headers', $attributes);

        $headers = $attributes['headers'];

        self::assertArrayHasKey('message_datetime', $headers);
        self::assertSame($epoch, strtotime($headers['message_datetime']));

        self::assertArrayHasKey('X-Planche', $headers);
        self::assertSame('gourdin', $headers['X-Planche']);

        self::assertArrayHasKey('X-Version', $headers);
        self::assertSame('1.0', $headers['X-Version']);
    }

    #[DataProvider('providerTestSetAttribute')]
    public function testSetAttribute($attributeName, $expectFind, $expectModification): void
    {
        $newValue = 'Dark sysadmin';

        $message = new Message('burger.over.ponies');
        $message->setAttribute($attributeName, $newValue);

        $attributes = $message->packAttributes();
        self::assertSame($expectFind, array_key_exists($attributeName, $attributes));

        if($expectFind === true)
        {
            self::assertSame($expectModification, $attributes[$attributeName] === $newValue);
        }
    }

    public static function providerTestSetAttribute(): array
    {
        return [
            ['app_id', true, true],
            ['timestamp', true, true],
            ['headers', true, false],
            ['big_pony', false, false],
            ['appid', false, false],
        ];
    }

    public function testHeaders(): void
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

        self::assertSame($gregoire, $headers['author']);
    }

    public function testSetExpiration(): void
    {
        $message = new Message('burger.over.ponies');
        $message->setExpiration(15);

        self::assertSame("15000", $message->getAttribute('expiration'));
    }

    public function testUnknownAttribute(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $message = new Message('burger.over.ponies');
        $message->getAttribute("Does not exist");
    }

    public function testSilentDropping(): void
    {
        $msg = new Message('my.key');

        self::assertTrue($msg->canBeDroppedSilently());

        $msg->disallowSilentDropping();
        self::assertFalse($msg->canBeDroppedSilently());
    }

    public function testAllowCompression(): void
    {
        $msg = new Message('my.key');

        self::assertFalse($msg->isCompressionAllowed());
        $msg->allowCompression();
        self::assertTrue($msg->isCompressionAllowed());
    }

    public function testChangingContentType(): void
    {
        $msg = new Message('my.key');
        self::assertContentTypeIs(ContentType::EMPTY_CONTENT, $msg);

        $msg->setText('unicorn');
        self::assertContentTypeIs(ContentType::TEXT, $msg);

        $msg->setJson([]);
        self::assertContentTypeIs(ContentType::JSON, $msg);

        $msg->packAttributes();
        self::assertContentTypeIs(ContentType::JSON, $msg);

        $msg->setStreamedBinary('pony', new ChunkSize(1));
        self::assertContentTypeIs(ContentType::BINARY, $msg);

        $msg->setText('pony');
        self::assertContentTypeIs(ContentType::TEXT, $msg);

        $msg->setAttribute(Message::ATTRIBUTE_CONTENT_TYPE, 'application/xml');
        self::assertContentTypeIs('application/xml', $msg);

        $msg->setJson([]);
        self::assertContentTypeIs('application/xml', $msg);

        $msg->packAttributes();
        self::assertContentTypeIs('application/xml', $msg);

        $msg->setAttribute(Message::ATTRIBUTE_CONTENT_TYPE, 'X-pony');
        self::assertContentTypeIs('X-pony', $msg);

        $msg->setJson([]);
        self::assertContentTypeIs('X-pony', $msg);

        $msg->packAttributes();
        self::assertContentTypeIs('X-pony', $msg);
    }

    private static function assertContentTypeIs($contentType, MessageMetadata $message): void
    {
        self::assertSame($contentType, $message->getContentType());
    }

    #[DataProvider('providerTestIsChunked')]
    public function testIsChunked(Message $message, $expected): void
    {
        self::assertSame($expected, $message->isChunked());
    }

    public static function providerTestIsChunked(): array
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
