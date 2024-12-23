<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Swarrot\Broker\Message;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\Bodies\Text;
use Puzzle\AMQP\Messages\InMemory;
use Puzzle\AMQP\WritableMessage;
use Puzzle\Assert\ArrayRelated;

class MessageAdapterTest extends TestCase
{
    use ArrayRelated;

    public function testText(): void
    {
        $body = <<<TEXT
Et interdum acciderat, ut siquid in penetrali secreto nullo citerioris vitae ministro praesente paterfamilias uxori
susurrasset in aurem, velut Amphiarao referente aut Marcio, quondam vatibus inclitis, postridie disceret imperator.
Ideoque etiam parietes arcanorum soli conscii timebantur.
TEXT;

        $properties = [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'burger.over.ponies',
            'app_id' => 'puzzle/ui'
        ];

        $swarrotMessage = new Message($body, $properties);
        $message = (new MessageAdapterFactory())->build($swarrotMessage);

        self::assertSame($body, $message->getBodyInOriginalFormat(), 'Decoded body must be unchanged');

        self::assertNotEmpty((string) $message);

        $attributes = $message->getAttributes();
        self::assertArrayHasKey('content_type', $attributes);
        self::assertArrayHasKey('routing_key', $attributes);

        self::assertSame('puzzle/ui', $message->getAppId());
    }

    public function testJson(): void
    {
        $decodedBody = [
            'burger' => 'McFat',
            'pizza' => [
                'tomato' => [
                    'Napoli', 'Reggina'
                ],
                'cream' => [
                    'Seafood'
                ],
            ],
        ];
        $body = json_encode($decodedBody);

        $properties = [
            'content_type' => ContentType::JSON,
            'routing_key' => 'burger.with.fries',
        ];

        $swarrotMessage = new Message($body, $properties);
        
        $message = (new MessageAdapterFactory())->build($swarrotMessage);
        
        self::assertSame($decodedBody, $message->getBodyInOriginalFormat());

        self::assertNotEmpty((string) $message);
    }

    public function testGetRoutingKeyFromHeader(): void
    {
        $swarrotMessage = new Message('body', [
            'headers' => [
                'routing_key' => 'my.routing.key.from.header',
            ],
            'routing_key' => 'my.routing.key',
            'content_type' => ContentType::TEXT,
        ]);

        $message = (new MessageAdapterFactory())->build($swarrotMessage);

        self::assertSame('my.routing.key', $message->getRoutingKey());
        self::assertSame('my.routing.key.from.header', $message->getRoutingKeyFromHeader());
    }

    public function testGetMissingRoutingKeyFromHeader(): void
    {
        $swarrotMessage = new Message('body', [
            'headers' => [
                'author' => 'Thierry Coquonneau',
            ],
            'routing_key' => 'my.routing.key',
            'content_type' => ContentType::TEXT,
        ]);

        $message = (new MessageAdapterFactory())->build($swarrotMessage);

        self::assertSame('my.routing.key', $message->getRoutingKey());
        self::assertNull($message->getRoutingKeyFromHeader());
    }

    public function testGetNonStandardAttribute(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $swarrotMessage = new Message('', [
            'content_type' => ContentType::EMPTY_CONTENT,
            'content_encoding' => 'utf-8'
        ]);
        $message = (new MessageAdapterFactory())->build($swarrotMessage);

        $message->getAttribute('not_an_amqp_attribute');
    }

    public function testInvalidConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $swarrotMessage = new Message('', [
            'no_content_type_attribute' => 2
        ]);
        
        $message = (new MessageAdapterFactory())->build($swarrotMessage);
    }

    #[DataProvider('providerTestIsLastRetry')]
    public function testIsLastRetry($nbTries, $max, $expected): void
    {
        $message = new MessageAdapter(new Message('', [
            'content_type' => ContentType::EMPTY_CONTENT,
            'headers' => [
                \Puzzle\AMQP\Consumers\Retry::DEFAULT_RETRY_HEADER => $nbTries,
            ]
        ]));

        self::assertSame($expected, $message->isLastRetry($max));
    }

    public static function providerTestIsLastRetry(): array
    {
        return [
            [0, 3, false],
            [1, 3, false],
            [2, 3, false],
            [3, 3, true],
            [4, 3, true],
            [5, 3, true],

            [0, 1, false],
            [1, 1, true],

            [0, 0, true],

            [0, -42, true],
        ];
    }

    public function testIsLastRetryWithoutHeader(): void
    {
        $message = new MessageAdapter(new Message('', [
            'content_type' => ContentType::EMPTY_CONTENT,
            'headers' => [],
        ]));

        self::assertFalse($message->isLastRetry());
        self::assertFalse($message->isLastRetry(0));
        self::assertFalse($message->isLastRetry(-1));
    }

    #[DataProvider('providerTestCloneIntoWritableMessage')]
    public function testCloneIntoWritableMessage($copyOldRoutingKey, $expectingRoutingKey): void
    {
        $readableMessage = InMemory::build('old.routing.key', new Text('This is fine'), [
                'h1' => 'title',
                'h2' => 'subtitle',
                'h3' => 'insignificant title',
                'author' => $jeanPierre = 'Jean-Pierre Fortune',
            ], [
                'content_encoding' => $iso = 'ISO-66642-1',
        ]);

        $message = $readableMessage->cloneIntoWritableMessage(
            new \Puzzle\AMQP\Messages\Message('new.routing.key'),
            $copyOldRoutingKey
        );

        self::assertInstanceOf(WritableMessage::class, $message);
        self::assertSame($expectingRoutingKey, $message->getRoutingKey());

        $headers = $message->getHeaders();
        $this->assertSameArrayExceptOrder(
                ['h1', 'h2', 'h3', 'author', 'routing_key', 'app_id', 'message_datetime'],
                array_keys($headers)
                );
        self::assertSame('subtitle', $headers['h2']);
        self::assertSame($jeanPierre, $headers['author']);

        self::assertSame($iso, $message->getAttribute('content_encoding'));
    }

    public static function providerTestCloneIntoWritableMessage(): array
    {
        return [
            [true, 'old.routing.key'],
            [false, 'new.routing.key'],
        ];
    }

    public function testToStringInvalidUtf8(): void
    {
        $invalidUTF8Message = new Message(
            mb_convert_encoding('Nêv£r gonnà lét yôù d¤wn', 'ISO-8859-1', 'UTF-8'),
            ['content_type' => ContentType::TEXT, 'routing_key' => 'zboui']
        );

        self::assertNotEmpty(
            (string) (new MessageAdapterFactory())->build($invalidUTF8Message)
        );
    }
}
