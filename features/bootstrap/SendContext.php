<?php

namespace Puzzle\AMQP\Contexts;

use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Bodies\Json;

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
    
    private function iSendMessage(Message $message)
    {
        $result = $this->client->publish($this->exchange, $message);
        
        \PHPUnit_Framework_Assert::assertTrue($result);
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
     * @Then The message in queue :queueName contains :content and is a json message
     */
    public function theMessageInQueueContainsAndIsAJsonMessage($content, $queueName)
    {
        $this->theMessageInQueueContains(self::JSON_ROUTING_KEY, $content, $queueName, "application/json");
    }
    
    private function theMessageInQueueContains($routingKey, $content, $queueName, $contentType)
    {
        $messages = $this->api->getMessagesFromQueue($this->vhost(), $queueName);
        $message = $messages->first();
        
        \PHPUnit_Framework_Assert::assertSame($routingKey, $message->routing_key);
        \PHPUnit_Framework_Assert::assertSame($content, $message->payload);
        \PHPUnit_Framework_Assert::assertSame($contentType, $message->properties['content_type']);
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
        
        \PHPUnit_Framework_Assert::assertSame($expectedNbMessages, $nbMessages);
    }
    
    private function nbMessagesInQueue($queueName)
    {
        $queue = $this->api->getQueue($this->vhost(), $queueName);
        
        return (int) $queue->messages;
    }
}
