<?php

namespace Puzzle\AMQP\Workers;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Consumers\Simple;
use Psr\Log\LoggerAwareTrait;
use Swarrot\Broker\Message;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\OnConsumeProcessor;
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

class CallableWorker implements Worker
{
    use LoggerAwareTrait;

    private
        $callable;

    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    public function process(ReadableMessage $message): bool
    {
        $callable = $this->callable;

        return $callable();
    }
}

class Collect implements Worker
{
    use LoggerAwareTrait;

    public
        $lastProcessedMessages = null;

    public function process(ReadableMessage $message): bool
    {
        $this->lastProcessedMessages = $message;

        return true;
    }
}

class ProcessInterfaceAdapterTest extends TestCase
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

    /**
     * @dataProvider providerTestCatchingThrowable
     */
    public function testCatchingThrowable(Worker $worker, $expectedException)
    {
        $workerContext = new WorkerContext(
            function() use($worker) {
                return $worker;
            },
            new Simple(),
            'fake_queue'
        );


        $processor = new ProcessorInterfaceAdapter($workerContext);
        $current = null;

        // Workaround : PHPUnit set an error handler -> unable to catch PHP native \Error
        // cf. http://php.net/manual/fr/function.set-error-handler.php
        $errorHandler = set_error_handler(null);
        try
        {
            $processor->process(new Message('body', [
                'content_type' => ContentType::TEXT,
                'routing_key' => 'ponies.over.unicorns',
            ]), []);
        }
        catch(\Throwable $current)
        {
        }

        set_error_handler($errorHandler);// Removing the workaround

        $this->assertInstanceOf($expectedException, $current);
    }

    public function providerTestCatchingThrowable()
    {
        return [
            'exception' => [
                'worker' => $this->callableWorker(function() {
                    throw new \Exception('Zboui zboui zboui');
                }),
                'expectedException' => \Exception::class,
            ],
            'error' => [
                'worker' => $this->callableWorker(function() {
                    throw new \Error('This is a PHP error');
                }),
                'expectedException' => \ErrorException::class,
            ],
        ];
    }

    private function callableWorker($callable): CallableWorker
    {
        return new CallableWorker($callable);
    }
}
