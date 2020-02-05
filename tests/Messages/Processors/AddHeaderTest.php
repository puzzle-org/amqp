<?php

namespace Puzzle\AMQP\Messages\Processors;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\WritableMessage;

class AddHeaderTest extends TestCase
{
    public function testOnPublish()
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
        
        $this->assertSomeHeaders([
            'pow' => 'wow',
            'vendor' => 'Soda Stevia',
            'author' => 'Lucien Pétochard',
            'routing_key' => 'my.key',
            'overrideBug' => 'original value',
            // 'message_datetime' => random value, not checkable in unit tests
        ], $message);
    }
    
    public function testAppendOrder()
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
        
        $this->assertSomeHeaders([
            'routing_key' => 'my.key',
            'Kimberley' => 'Tartines',
            'Eat' => 'me',
            'eAT' => 'you',
             // 'message_datetime' => random value, not checkable in unit tests
        ], $message);
    }
   
    
    private function assertSomeHeaders($expectedHeaders, WritableMessage $message)
    {
        foreach($expectedHeaders as $header => $value)
        {
            $this->assertHeaderIsPresent($header, $value, $message->getHeaders());
        }
    }
        
    private function assertHeaderIsPresent($headerName, $value, array $headerList)
    {
        $this->assertArrayHasKey($headerName, $headerList);
        $this->assertSame($value, $headerList[$headerName]);
    }
}
