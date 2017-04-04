<?php

namespace Puzzle\AMQP\Clients;

use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Processors\NullProcessor;

class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $client = new InMemory();
        $client->setMessageProcessors([
            new NullProcessor(),
            new NullProcessor(),
        ]);

        $this->assertEmpty($client->getSentMessages());

        $message = new Message('key');
        $client->publish('myEx', $message);

        $sentMessages = $client->getSentMessages();
        $this->assertCount(1, $sentMessages);
        foreach($sentMessages as $messageInfo)
        {
            $this->assertSame($message, $messageInfo['message']);
        }

        $this->assertCount(1, $client->getSentMessages(), 'idempotent');

        $client->dropSentMessages();
        $this->assertEmpty($client->getSentMessages());
    }
}
