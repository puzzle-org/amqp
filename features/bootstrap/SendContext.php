<?php

namespace Puzzle\AMQP\Contexts;

use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Processors\GZip;

class SendContext extends AbstractRabbitMQContext
{
    /**
     * @Given The queue :queue is empty
     */
    public function theQueueIsEmpty($queue)
    {
        $this->api->purgeQueue($this->vhost(), $queue);
        $this->assertMessagesInQueue($queue, 0);
    }
    
    /**
     * @When I send the text message :bodyContent
     */
    public function iSendTheTextMessageWithRoutingKey($bodyContent)
    {
        $message = new Message(self::TEXT_ROUTING_KEY);
        $message->setText($bodyContent);
     
        $this->iSendMessage($message);
    }
    
    /**
     * @When I send the xml message :bodyContent
     */
    public function iSendTheXmlMessage($bodyContent)
    {
        $message = new Message(self::XML_ROUTING_KEY);
        $message->setAttribute('content_type', 'application/xml');
        $message->setText($bodyContent);

        $this->iSendMessage($message);
    }

    /**
     * @When I send the json message :bodyContent
     */
    public function iSendTheJsonMessageWithRoutingKey($bodyContent)
    {
        $message = new Message(self::JSON_ROUTING_KEY);
        
        $body = new Json();
        $body->changeContentWithJson($bodyContent);
        $message->setBody($body);
     
        $this->iSendMessage($message);
    }
    
    /**
     * @When I send the gzipped text message :bodyContent
     */
    public function iSendTheGzippedTextMessage($bodyContent)
    {
        $message = new Message(self::TEXT_ROUTING_KEY);
        $message->setText($bodyContent);
        $message->allowCompression();

        $this->iSendMessage($message);
    }

    private function iSendMessage(Message $message)
    {
        $result = $this->client->publish($this->exchange, $message);
        
        \PHPUnit\Framework\Assert::assertTrue($result);
    }
    
    /**
     * @Then The queue :queue must contain :nbMessages message
     */
    public function theQueueMustContainMessage($queue, $nbMessages)
    {
        $this->assertMessagesInQueue($queue, (int) $nbMessages);
    }
    
    /**
     * @Then The message in queue :queueName contains :content and is a text message
     */
    public function theMessageInQueueContainsAndIsATextMessage($content, $queueName)
    {
        $this->theMessageInQueueContains(self::TEXT_ROUTING_KEY, $content, $queueName, "text/plain");
    }
    
    /**
     * @Then The message in queue :queueName contains :content and is a xml message
     */
    public function theMessageInQueueContainsAndIsAXmlMessage($content, $queueName)
    {
        $this->theMessageInQueueContains(self::XML_ROUTING_KEY, $content, $queueName, "application/xml");
    }
    
    /**
     * @Then The message in queue :queueName contains :content and is a json message
     */
    public function theMessageInQueueContainsAndIsAJsonMessage($content, $queueName)
    {
        $this->theMessageInQueueContains(self::JSON_ROUTING_KEY, $content, $queueName, "application/json");
    }
    
    /**
     * @Then The message in queue :queueName contains a gzipped message
     */
    public function theMessageInQueueContainsAGzippedMessage($queueName)
    {
        $message = $this->theMessageInQueueContains(self::TEXT_ROUTING_KEY, false, $queueName, ContentType::BINARY);

        \PHPUnit\Framework\Assert::assertArrayHasKey('headers', $message->properties);
        $headers = $message->properties['headers'];

        \PHPUnit\Framework\Assert::assertTrue(is_array($headers));
        \PHPUnit\Framework\Assert::assertArrayHasKey(GZip::HEADER_COMPRESSION, $headers);
        \PHPUnit\Framework\Assert::assertArrayHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $headers);
        \PHPUnit\Framework\Assert::assertSame(Gzip::COMPRESSION_ALGORITHM, $headers[Gzip::HEADER_COMPRESSION]);
        \PHPUnit\Framework\Assert::assertSame(ContentType::TEXT, $headers[Gzip::HEADER_COMPRESSION_CONTENT_TYPE]);
    }
    
    private function theMessageInQueueContains($routingKey, $content, $queueName, $contentType)
    {
        $messages = $this->api->getMessagesFromQueue($this->vhost(), $queueName);
        $message = $messages->first();
        
        \PHPUnit\Framework\Assert::assertSame($routingKey, $message->routing_key);
        \PHPUnit\Framework\Assert::assertSame($contentType, $message->properties['content_type']);

        if($content !== false)
        {
            \PHPUnit\Framework\Assert::assertSame($content, $message->payload);
        }

        return $message;
    }
    
    private function assertMessagesInQueue($queue, $expectedNbMessages, $waitingSeconds = 11)
    {
        $nbMessages = $this->nbMessagesInQueue($queue);
        $nbTries = 0;
        
        while($nbMessages !== $expectedNbMessages && $nbTries < $waitingSeconds)
        {
            sleep(1);
            $nbTries++;
            
            $nbMessages = $this->nbMessagesInQueue($queue);
        }
        
        \PHPUnit\Framework\Assert::assertSame($expectedNbMessages, $nbMessages);
    }
    
    private function nbMessagesInQueue($queueName)
    {
        $queue = $this->api->getQueue($this->vhost(), $queueName);
        
        return (int) $queue->messages;
    }
}
