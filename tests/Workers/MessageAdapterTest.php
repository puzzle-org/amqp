<?php

namespace Puzzle\AMQP\Workers;

use Swarrot\Broker\Message;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\Bodies\Text;

class MessageAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testText()
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
        $message = new MessageAdapter($swarrotMessage);

        $this->assertSame($body, $message->getBodyInOriginalFormat(), 'Decoded body must be unchanged');
        $this->assertTrue($message->getBody() instanceof Text);

        $this->assertNotEmpty((string) $message);

        $attributes = $message->getAttributes();
        $this->assertArrayHasKey('content_type', $attributes);
        $this->assertArrayHasKey('routing_key', $attributes);

        $this->assertSame('puzzle/ui', $message->getAppId());
    }

    public function testJson()
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
        $message = new MessageAdapter($swarrotMessage);

        $this->assertSame($decodedBody, $message->getBodyInOriginalFormat());
        $this->assertTrue($message->getBody() instanceof Json);

        $this->assertNotEmpty((string) $message);
    }

    public function testGetRoutingKeyFromHeader()
    {
        $swarrotMessage = new Message('body', [
            'headers' => [
                'routing_key' => 'my.routing.key.from.header',
            ],
            'routing_key' => 'my.routing.key',
            'content_type' => ContentType::TEXT,
        ]);

        $message = new MessageAdapter($swarrotMessage);

        $this->assertSame('my.routing.key', $message->getRoutingKey());
        $this->assertSame('my.routing.key.from.header', $message->getRoutingKeyFromHeader());
    }

    public function testGetMissingRoutingKeyFromHeader()
    {
        $swarrotMessage = new Message('body', [
            'headers' => [
                'author' => 'Thierry Coquonneau',
            ],
            'routing_key' => 'my.routing.key',
            'content_type' => ContentType::TEXT,
        ]);

        $message = new MessageAdapter($swarrotMessage);

        $this->assertSame('my.routing.key', $message->getRoutingKey());
        $this->assertNull($message->getRoutingKeyFromHeader());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetNonStandardAttribute()
    {
        $swarrotMessage = new Message('', [
            'content_type' => ContentType::EMPTY_CONTENT,
            'content_encoding' => 'utf-8'
        ]);
        $message = new MessageAdapter($swarrotMessage);

        $message->getAttribute('not_an_amqp_attribute');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConstruction()
    {
        $message = new MessageAdapter(new Message('', [
            'no_content_type_attribute' => 2
        ]));
    }

    /**
     * @dataProvider providerTestIsLastRetry
     */
    public function testIsLastRetry($nbTries, $max, $expected)
    {
        $message = new MessageAdapter(new Message('', [
            'content_type' => ContentType::EMPTY_CONTENT,
            'headers' => [
                \Puzzle\AMQP\Consumers\Retry::DEFAULT_RETRY_HEADER => $nbTries,
            ]
        ]));

        $this->assertSame($expected, $message->isLastRetry($max));
    }

    public function providerTestIsLastRetry()
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

    public function testIsLastRetryWithoutHeader()
    {
        $message = new MessageAdapter(new Message('', [
            'content_type' => ContentType::EMPTY_CONTENT,
            'headers' => [],
        ]));

        $this->assertFalse($message->isLastRetry());
        $this->assertFalse($message->isLastRetry(0));
        $this->assertFalse($message->isLastRetry(-1));
    }
}
