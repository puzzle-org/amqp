<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Contexts;

use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Consumers\Insomniac;
use Puzzle\AMQP\Workers\Worker;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Workers\ProcessorInterfaceAdapter;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\Processors\GZip;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use PHPUnit\Framework\Assert;

final class ConsumeContext extends AbstractRabbitMQContext implements Worker
{
    use LoggerAwareTrait;
    
    private array
        $consumedMessages;
    
    public function __construct($path)
    {
        parent::__construct($path);
        
        $this->logger = new NullLogger();
        $this->consumedMessages = [];
    }

    /**
     * @Given The queue :queue contains only the text message :bodyContent
     */
    public function theQueueContainsOnlyTheTextMessage(string $bodyContent, string $queue): void
    {
        $this->httpClient->purgeQueue($this->vhost, $queue);

        $this->theQueueContainsTheTextMessage($bodyContent, $queue);
    }
    
    /**
     * @Given The queue :queue contains the text message :bodyContent
     */
    public function theQueueContainsTheTextMessage(string $bodyContent, string $queue): void
    {
        // FIXME Use RabbitMQCTL instead

        $message = new Message(self::TEXT_ROUTING_KEY);
        $message->setText($bodyContent);
        
        $this->client->publish(self::EXCHANGE, $message);
    }

    /**
     * @Given The queue :queue contains the json message :bodyContent
     */
    public function theQueueContainsTheJsonMessage(string $bodyContent, string $queue): void
    {
        // FIXME Use RabbitMQCTL instead

        $message = new Message(self::JSON_ROUTING_KEY);
        
        $body = new Json();
        $body->changeContentWithJson($bodyContent);
        $message->setBody($body);
        
        $this->client->publish(self::EXCHANGE, $message);
    }
    
    /**
     * @When I consume all the messages in the queue :queue
     */
    public function iConsumeAllTheMessagesInTheQueue(string $queue): void
    {
        $this->consumedMessages = [];

        $consumer = new Insomniac();
        $processor = new ProcessorInterfaceAdapter($this);
        $processor->appendMessageProcessor(new GZip());

        $consumer->consume($processor, $this->client, $queue);
    }
    
    public function process(ReadableMessage $message): void
    {
        $this->consumedMessages[] = $message;
    }
    
    /**
     * @Then /I have consumed (\d+) messages?/
     */
    public function iHaveConsumedMessage(int $nbMessages): void
    {
        Assert::assertCount($nbMessages, $this->consumedMessages);
    }
    
    /**
     * @Then the message is a text one
     */
    public function theMessageIsATextOne(): void
    {
        $this->theMessageIs(self::TEXT_ROUTING_KEY, ContentType::TEXT);
    }
    
    /**
     * @Then the message is a json one
     */
    public function theMessageIsAJsonOne(): void
    {
        $this->theMessageIs(self::JSON_ROUTING_KEY, ContentType::JSON);
    }
    
    private function theMessageIs(string $routingKey, string $contentType): void
    {
        $firstMessage = $this->consumedMessages[0];
        
        Assert::assertSame($firstMessage->getRoutingKeyFromHeader(), $routingKey);
        Assert::assertSame($firstMessage->getContentType(), $contentType);
    }
    
    /**
     * @Then the message contains the json :jsonString
     */
    public function theMessageContainsTheJson($jsonString): void
    {
        $this->theMessageContains(json_decode($jsonString, true));
    }
    
    /**
     * @Then the message contains :bodyContent
     */
    public function theMessageContains(mixed $bodyContent): void
    {
        $firstMessage = $this->consumedMessages[0];
        
        Assert::assertSame($firstMessage->getBodyInOriginalFormat(), $bodyContent);
    }

    /**
     * @Then one of the messages is a text one
     */
    public function oneOfTheMessagesIsATextOne(): void
    {
        $this->oneOfTheMessagesIs(ContentType::TEXT, self::TEXT_ROUTING_KEY);
    }

    /**
     * @Then one of the messages is a json one
     */
    public function oneOfTheMessagesIsAJsonOne(): void
    {
        $this->oneOfTheMessagesIs(ContentType::JSON, self::JSON_ROUTING_KEY);
    }
    
    private function oneOfTheMessagesIs(string $contentType, string $routingKey): void
    {
        $found = null;
        
        foreach($this->consumedMessages as $message)
        {
            if($message->getContentType() === $contentType)
            {
                $found = $message;
                break;
            }
        }
        
        Assert::assertNotNull($found);
        Assert::assertSame($routingKey, $found->getRoutingKeyFromHeader());
    }
    
    /**
     * @Then one of the messages contains the json :jsonString
     */
    public function oneOfTheMessagesContainsTheJson(string $jsonString): void
    {
        $this->oneOfTheMessagesContains(json_decode($jsonString, true));
    }
    
    /**
     * @Then one of the messages contains :bodyContent
     */
    public function oneOfTheMessagesContains(mixed $bodyContent): void
    {
        $found = false;
        
        foreach($this->consumedMessages as $message)
        {
            if($message->getBodyInOriginalFormat() === $bodyContent)
            {
                $found = true;
                break;
            }
        }
        
        Assert::assertTrue($found);
    }

    /**
     * @Given The queue :queue contains only the compressed text message :bodyContent
     */
    public function theQueueContainsOnlyTheCompressedTextMessage(string $bodyContent, string $queue): void
    {
        $this->httpClient->purgeQueue($this->vhost, $queue);

        $this->theQueueContainsTheCompressedTextMessage($bodyContent, $queue);
    }

    /**
     * @Given The queue :queue contains the compressed text message :bodyContent
     */
    public function theQueueContainsTheCompressedTextMessage(string $bodyContent, string $queue): void
    {
        $message = new Message(self::COMPRESSED_ROUTING_KEY);
        $message->setText($bodyContent);
        $message->allowCompression();

        $this->client->publish(self::EXCHANGE, $message);
    }

    /**
     * @Then the message is an uncompressed text one
     */
    public function theMessageIsAnUncompressedTextOne(): void
    {
        $this->theMessageIs(self::COMPRESSED_ROUTING_KEY, ContentType::TEXT);
    }
}
