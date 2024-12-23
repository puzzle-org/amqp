<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Processors;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\WritableMessage;

class AddHeaderTest extends TestCase
{
    public function testOnPublish(): void
    {
        $message = new Message('my.key');
        $message
            ->addHeader('pow', 'wow')
            ->addHeader('overrideBug', 'original value');
        
        $processor = new AddHeader([
            'vendor' => 'Soda Stevia',
            'overrideBug' => 'must not appear at the end',
        ]);
        $processor->addHeader('author', 'Lucien Pétochard');
        
        $client = new InMemory();
        $client->appendMessageProcessor($processor);
        $client->publish('ex', $message);
        
        self::assertSomeHeaders([
            'pow' => 'wow',
            'vendor' => 'Soda Stevia',
            'author' => 'Lucien Pétochard',
            'routing_key' => 'my.key',
            'overrideBug' => 'original value',
            // 'message_datetime' => random value, not checkable in unit tests
        ], $message);
    }
    
    public function testAppendOrder(): void
    {
        $message = new Message('my.key');
        
        $client = new InMemory();
        $client->appendMessageProcessor(new AddHeader([
            'Kimberley' => 'Tartines',
        ]));
        $client->appendMessageProcessor(new AddHeader([
            'Eat' => 'me',
        ]));
        $client->appendMessageProcessor(new AddHeader([
            'Kimberley' => 'Steaks',
            'eAT' => 'you',
        ]));
        $client->publish('ex', $message);
        
        self::assertSomeHeaders([
            'routing_key' => 'my.key',
            'Kimberley' => 'Tartines',
            'Eat' => 'me',
            'eAT' => 'you',
             // 'message_datetime' => random value, not checkable in unit tests
        ], $message);
    }
   
    
    private static function assertSomeHeaders($expectedHeaders, WritableMessage $message): void
    {
        foreach($expectedHeaders as $header => $value)
        {
            self::assertHeaderIsPresent($header, $value, $message->getHeaders());
        }
    }
        
    private static function assertHeaderIsPresent($headerName, $value, array $headerList): void
    {
        self::assertArrayHasKey($headerName, $headerList);
        self::assertSame($value, $headerList[$headerName]);
    }
}
