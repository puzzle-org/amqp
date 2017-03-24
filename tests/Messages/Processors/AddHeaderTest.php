<?php

namespace Puzzle\AMQP\Messages\Processors;

use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\WritableMessage;

class AddHeaderTest extends \PHPUnit_Framework_TestCase
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
        $client->addMessageProcessor($processor);
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
