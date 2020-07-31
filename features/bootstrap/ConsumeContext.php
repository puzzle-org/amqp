<?php

namespace Puzzle\AMQP\Contexts;

use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Consumers\Insomniac;
use Puzzle\AMQP\Workers\Worker;
use Puzzle\AMQP\Workers\WorkerContext;
use Puzzle\AMQP\ReadableMessage;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Workers\ProcessorInterfaceAdapter;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\Processors\GZip;

class ConsumeContext extends AbstractRabbitMQContext implements Worker
{
    use LoggerAwareTrait;
    
    private
        $consumedMessages;
    
    public function __construct($path)
    {
        parent::__construct($path);
        
        $this->logger = new NullLogger();
        $this->consumedMessages = [];
    }
    
    /**
     * @Given The queue :queue contains the text message :bodyContent
     */
    public function theQueueContainsTheTextMessage($bodyContent, $queue)
    {
        // FIXME Use RabbitMQCTL instead
        
        $message = new Message(self::TEXT_ROUTING_KEY);
        $message->setText($bodyContent);
        
        $this->client->publish($this->exchange, $message);
    }

    /**
     * @Given The queue :queue contains the json message :bodyContent
     */
    public function theQueueContainsTheJsonMessage($bodyContent, $queue)
    {
        // FIXME Use RabbitMQCTL instead
        
        $message = new Message(self::JSON_ROUTING_KEY);
        
        $body = new Json();
        $body->changeContentWithJson($bodyContent);
        $message->setBody($body);
        
        $this->client->publish($this->exchange, $message);
    }
    
    /**
     * @When I consume all the messages in the queue :queue
     */
    public function iConsumeAllTheMessagesInTheQueue($queue)
    {
        $this->consumedMessages = [];
        $workerContext = new WorkerContext(
            function() {
                return $this;
            },
            $consumer = new Insomniac(),
            $queue
        );
        
        $processor = new ProcessorInterfaceAdapter($workerContext);
        $processor->appendMessageProcessor(new GZip());

        $consumer->consume($processor, $this->client, $workerContext);
    }
    
    public function process(ReadableMessage $message): bool
    {
        $this->consumedMessages[] = $message;
        return true;
    }
    
    /**
     * @Then /I have consumed (\d+) messages?/
     */
    public function iHaveConsumedMessage($nbMessages)
    {
        \PHPUnit\Framework\Assert::assertSame((int) $nbMessages, count($this->consumedMessages));
    }
    
    /**
     * @Then the message is a text one
     */
    public function theMessageIsATextOne()
    {
        $this->theMessageIs(self::TEXT_ROUTING_KEY, ContentType::TEXT);
    }
    
    /**
     * @Then the message is a json one
     */
    public function theMessageIsAJsonOne()
    {
        $this->theMessageIs(self::JSON_ROUTING_KEY, ContentType::JSON);
    }
    
    private function theMessageIs($routingKey, $contentType)
    {
        $firstMessage = $this->consumedMessages[0];
        
        \PHPUnit\Framework\Assert::assertSame($firstMessage->getRoutingKeyFromHeader(), $routingKey);
        \PHPUnit\Framework\Assert::assertSame($firstMessage->getContentType(), $contentType);
    }
    
    /**
     * @Then the message contains the json :jsonString
     */
    public function theMessageContainsTheJson($jsonString)
    {
        $this->theMessageContains(json_decode($jsonString, true));
    }
    
    /**
     * @Then the message contains :bodyContent
     */
    public function theMessageContains($bodyContent)
    {
        $firstMessage = $this->consumedMessages[0];
        
        \PHPUnit\Framework\Assert::assertSame($firstMessage->getBodyInOriginalFormat(), $bodyContent);
    }

    /**
     * @Then one of the messages is a text one
     */
    public function oneOfTheMessagesIsATextOne()
    {
        $this->oneOfTheMessagesIs(ContentType::TEXT, self::TEXT_ROUTING_KEY);
    }

    /**
     * @Then one of the messages is a json one
     */
    public function oneOfTheMessagesIsAJsonOne()
    {
        $this->oneOfTheMessagesIs(ContentType::JSON, self::JSON_ROUTING_KEY);
    }
    
    private function oneOfTheMessagesIs($contentType, $routingKey)
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
        
        \PHPUnit\Framework\Assert::assertNotNull($found);
        \PHPUnit\Framework\Assert::assertSame($routingKey, $found->getRoutingKeyFromHeader());
    }
    
    /**
     * @Then one of the messages contains the json :jsonString
     */
    public function oneOfTheMessagesContainsTheJson($jsonString)
    {
        $this->oneOfTheMessagesContains(json_decode($jsonString, true));
    }
    
    /**
     * @Then one of the messages contains :bodyContent
     */
    public function oneOfTheMessagesContains($bodyContent)
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
        
        \PHPUnit\Framework\Assert::assertTrue($found);
    }

    /**
     * @Given The queue :queue contains the compressed text message :bodyContent
     */
    public function theQueueContainsTheCompressedTextMessage($bodyContent, $queue)
    {
        $message = new Message(self::COMPRESSED_ROUTING_KEY);
        $message->setText($bodyContent);
        $message->allowCompression();

        $this->client->publish($this->exchange, $message);
    }

    /**
     * @Then the message is an uncompressed text one
     */
    public function theMessageIsAnUncompressedTextOne()
    {
        $this->theMessageIs(self::COMPRESSED_ROUTING_KEY, ContentType::TEXT);
    }
}
