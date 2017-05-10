<?php

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Consumers\Simple;
use Psr\Log\LoggerAwareTrait;
use Swarrot\Broker\Message;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\OnConsumeProcessor;
use Puzzle\AMQP\Workers\ReadableMessageModifier;
use Puzzle\AMQP\Messages\Bodies\Text;
use Puzzle\Pieces\EventDispatcher\Adapters\Symfony;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Puzzle\AMQP\Messages\BodyFactories\Standard;
use Psr\Log\NullLogger;

class ChangeBodyProcessor implements OnConsumeProcessor
{
    use LoggerAwareTrait;
    
    private
    $text;
    
    public function __construct($text)
    {
        $this->text = $text;
    }
    
    public function onConsume(ReadableMessage $message)
    {
        $builder = new ReadableMessageModifier($message);
        $builder->changeBody(new Text($this->text));
        
        return $builder->build();
    }
}

class Collect implements Worker
{
    use LoggerAwareTrait;

    public
        $lastProcessedMessages = null;

    public function process(ReadableMessage $message)
    {
        $this->lastProcessedMessages = $message;
    }
}

class ProcessInterfaceAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $worker = new Collect();
        $workerClosure = function() use($worker) {
            return $worker;
        };

        $workerContext = new WorkerContext($workerClosure, new Simple(), 'fake_queue');

        $processor = new ProcessorInterfaceAdapter($workerContext);
        $message = new Message('body', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]);
        $processor->process($message, []);

        $this->assertTrue($worker->lastProcessedMessages instanceof ReadableMessage);
        $this->assertSame('ponies.over.unicorns', $worker->lastProcessedMessages->getRoutingKey());
    }
    
    public function testProcessWithCustomDependencies()
    {
        $worker = new Collect();
        $workerClosure = function() use($worker) {
            return $worker;
        };

        $workerContext = new WorkerContext($workerClosure, new Simple(), 'fake_queue');
        $bodyFactory = new Standard();
        $bodyFactory->handleContentType('application/x-custom', new \Puzzle\AMQP\Messages\TypedBodyFactories\Text());
        
        $processor = new ProcessorInterfaceAdapter($workerContext);
        $processor
            ->setEventDispatcher(new Symfony(new EventDispatcher()))
            ->setMessageAdapterFactory(new MessageAdapterFactory($bodyFactory));
        
        $message = new Message('body', [
            'content_type' => 'application/x-custom',
            'routing_key' => 'ponies.over.unicorns',
        ]);
        $processor->process($message, []);

        $this->assertTrue($worker->lastProcessedMessages instanceof ReadableMessage);
        $this->assertSame('ponies.over.unicorns', $worker->lastProcessedMessages->getRoutingKey());
    }
    
    public function testOnConsume()
    {
        $worker = new Collect();
        $workerClosure = function() use($worker) {
            return $worker;
        };

        $workerContext = new WorkerContext($workerClosure, new Simple(), 'fake_queue');
        $workerContext->setLogger(new NullLogger());
        $workerContext->setWorkerLogger(new NullLogger());

        $processor = new ProcessorInterfaceAdapter($workerContext);
        $processor->appendMessageProcessor(new ChangeBodyProcessor('pony'));
        
        $processor->process(new Message('horse', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]), []);
        
        $this->assertSame('pony', $worker->lastProcessedMessages->getBodyInOriginalFormat());

        $processor->process(new Message('lamb', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]), []);
        
        $this->assertSame('pony', $worker->lastProcessedMessages->getBodyInOriginalFormat());
        
        $processor->appendMessageProcessor(new ChangeBodyProcessor('unicorn'));

        $processor->process(new Message('donkey', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]), []);
        
        $this->assertSame('pony', $worker->lastProcessedMessages->getBodyInOriginalFormat());
        
        $processor->setMessageProcessors([
            new ChangeBodyProcessor('pegasus'),
            new ChangeBodyProcessor('unicorn'),
            new ChangeBodyProcessor('pony'),
        ]);

        $processor->process(new Message('donkey', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]), []);
        
        $this->assertSame('pegasus', $worker->lastProcessedMessages->getBodyInOriginalFormat());
    }
}
