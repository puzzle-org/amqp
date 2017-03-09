<?php

namespace Puzzle\AMQP\Clients\Decorators;

use Puzzle\AMQP\Clients\InMemory;
use Puzzle\AMQP\Messages\Json;
use Psr\Log\NullLogger;

class PrefixedExchangesClientTest extends \PHPUnit_Framework_TestCase
{
    private
        $memory;
    
    protected function setUp()
    {
        $this->memory = new InMemory();
    }
    
    /**
     * @dataProvider providerTestPublish
     */
    public function testPublish($prefix, $expectedExchange)
    {
        $client = new PrefixedExchangesClient($this->memory, $prefix);
        $client->setLogger(new NullLogger());
        
        $message = new Json('routing.key');
        $client->publish('unicorn', $message);
    
        $sentMessages = $this->memory->getSentMessages();
        $this->assertCount(1, $sentMessages);
        
        $firstMessage = array_shift($sentMessages);
        
        $this->assertSame($message, $firstMessage['message']);
        $this->assertSame($expectedExchange, $firstMessage['exchange']);
    }
    
    public function providerTestPublish()
    {
        return [
            'nominal' =>
                ['pony', 'pony.unicorn'],
            'left space' =>
                ['       spaceMe', 'spaceMe.unicorn'],
            'left and right space' =>
                ['       space.me    ', 'space.me.unicorn'],
            'multiple keys' =>
                ['burger.fat', 'burger.fat.unicorn'],
            'empty string' =>
                ['', 'unicorn'],
            'null' =>
                [null, 'unicorn'],
        ];
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testGetQueue()
    {
        $client = new PrefixedExchangesClient($this->memory, 'rainbow');
        $client->getQueue('tail');
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testGetExchange()
    {
        $client = new PrefixedExchangesClient($this->memory, 'rainbow');
        $client->getExchange('one');
    }
}
