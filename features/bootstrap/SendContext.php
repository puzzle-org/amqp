<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Contexts;

use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Processors\GZip;
use PHPUnit\Framework\Assert;

final class SendContext extends AbstractRabbitMQContext
{
    /**
     * @Given The queue :queue is empty
     */
    public function theQueueIsEmpty(string $queue): void
    {
        $this->client->getQueue($queue)->purge();

        $this->assertMessagesInQueue($queue, 0);
    }
    
    /**
     * @When I send the text message :bodyContent
     */
    public function iSendTheTextMessageWithRoutingKey(string $bodyContent): void
    {
        $message = new Message(self::TEXT_ROUTING_KEY);
        $message->setText($bodyContent);
     
        $this->iSendMessage($message);
    }
    
    /**
     * @When I send the xml message :bodyContent
     */
    public function iSendTheXmlMessage(string $bodyContent): void
    {
        $message = new Message(self::XML_ROUTING_KEY);
        $message->setAttribute('content_type', 'application/xml');
        $message->setText($bodyContent);

        $this->iSendMessage($message);
    }

    /**
     * @When I send the json message :bodyContent
     */
    public function iSendTheJsonMessageWithRoutingKey(string $bodyContent): void
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
    public function iSendTheGzippedTextMessage(string $bodyContent): void
    {
        $message = new Message(self::TEXT_ROUTING_KEY);
        $message->setText($bodyContent);
        $message->allowCompression();

        $this->iSendMessage($message);
    }

    private function iSendMessage(Message $message): void
    {
        $result = $this->client->publish(self::EXCHANGE, $message);
        
        \PHPUnit\Framework\Assert::assertTrue($result);
    }
    
    /**
     * @Then The queue :queue must contain :nbMessages message
     */
    public function theQueueMustContainMessage(string $queue, int $nbMessages): void
    {
        $this->assertMessagesInQueue($queue, $nbMessages);
    }
    
    /**
     * @Then The message in queue :queueName contains :content and is a text message
     */
    public function theMessageInQueueContainsAndIsATextMessage(string $content, string $queueName): void
    {
        $this->theMessageInQueueContains(self::TEXT_ROUTING_KEY, $content, $queueName, "text/plain");
    }
    
    /**
     * @Then The message in queue :queueName contains :content and is a xml message
     */
    public function theMessageInQueueContainsAndIsAXmlMessage(string $content, string $queueName): void
    {
        $this->theMessageInQueueContains(self::XML_ROUTING_KEY, $content, $queueName, "application/xml");
    }
    
    /**
     * @Then The message in queue :queueName contains :content and is a json message
     */
    public function theMessageInQueueContainsAndIsAJsonMessage(string $content, string $queueName): void
    {
        $this->theMessageInQueueContains(self::JSON_ROUTING_KEY, $content, $queueName, "application/json");
    }
    
    /**
     * @Then The message in queue :queueName contains a gzipped message
     */
    public function theMessageInQueueContainsAGzippedMessage(string $queueName): void
    {
        $message = $this->theMessageInQueueContains(self::TEXT_ROUTING_KEY, false, $queueName, ContentType::BINARY);

        $headers = $message['properties']['headers'];

        Assert::assertIsArray($headers);
        Assert::assertArrayHasKey(GZip::HEADER_COMPRESSION, $headers);
        Assert::assertArrayHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $headers);
        Assert::assertSame(Gzip::COMPRESSION_ALGORITHM, $headers[Gzip::HEADER_COMPRESSION]);
        Assert::assertSame(ContentType::TEXT, $headers[Gzip::HEADER_COMPRESSION_CONTENT_TYPE]);
    }
    
    private function theMessageInQueueContains(string $routingKey, string|bool $content, string $queueName, string $contentType): array
    {
        $firstMessageOptions = ['count' => 1, 'ackmode' => 'ack_requeue_true', 'encoding' => 'auto', 'truncate' => 50000];
        $message = $this->httpClient->getMessages($this->vhost, $queueName, $firstMessageOptions)[0];

        Assert::assertSame($routingKey, $message['routing_key']);
        Assert::assertSame($contentType,  $message['properties']['content_type']);

        if($content !== false)
        {
            Assert::assertSame($content, $message['payload']);
        }

        return $message;
    }
    
    private function assertMessagesInQueue(string $queue, int $expectedNbMessages, int $waitingSeconds = 11): void
    {
        $nbMessages = $this->nbMessagesInQueue($queue);
        $nbTries = 0;
        
        while($nbMessages !== $expectedNbMessages && $nbTries < $waitingSeconds)
        {
            sleep(1);
            $nbTries++;
            
            $nbMessages = $this->nbMessagesInQueue($queue);
        }
        
        Assert::assertSame($expectedNbMessages, $nbMessages);
    }
    
    private function nbMessagesInQueue(string $queueName): int
    {
        return (int) ($this->httpClient->queueInfo($this->vhost, $queueName)['messages'] ?? 0);
    }
}
