<?php

namespace Puzzle\AMQP\Clients;

use Puzzle\AMQP\Messages\InMemoryJson;

class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $client = new InMemory();

        $this->assertEmpty($client->getSentMessages());

        $message = new InMemoryJson('key');
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
