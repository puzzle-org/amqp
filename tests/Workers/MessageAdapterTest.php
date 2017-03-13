<?php

namespace Puzzle\AMQP\Workers;

use Swarrot\Broker\Message;
use Puzzle\AMQP\Messages\ContentType;

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
        ];
        
        $swarrotMessage = new Message($body, $properties);
        $message = new MessageAdapter($swarrotMessage);
        
        $this->assertSame($body, $message->getRawBody(), 'Raw body must be unchanged');
        $this->assertSame($body, $message->getDecodedBody(), 'Decoded body must be unchanged');
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
        ];
        
        $swarrotMessage = new Message($body, $properties);
        $message = new MessageAdapter($swarrotMessage);
        
        $this->assertSame($body, $message->getRawBody());
        $this->assertSame($decodedBody, $message->getDecodedBody());
    }
}
