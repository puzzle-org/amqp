<?php

namespace Puzzle\AMQP\Workers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\ReadableMessage;
use Psr\Log\LoggerAwareTrait;
use Swarrot\Broker\Message;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\Messages\OnConsumeProcessor;
use Puzzle\AMQP\Messages\Bodies\Text;
use Puzzle\Pieces\EventDispatcher\Adapters\Symfony;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Puzzle\AMQP\Messages\BodyFactories\Standard;

class ChangeBodyProcessor implements OnConsumeProcessor
{
    use LoggerAwareTrait;
    
    private string
        $text;
    
    public function __construct(string $text)
    {
        $this->text = $text;
    }
    
    public function onConsume(ReadableMessage $message): ReadableMessage
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

    public function process(ReadableMessage $message): void
    {
        $callable = $this->callable;

        $callable();
    }
}

class Collect implements Worker
{
    use LoggerAwareTrait;

    public ?ReadableMessage
        $lastProcessedMessages = null;

    public function process(ReadableMessage $message): void
    {
        $this->lastProcessedMessages = $message;
    }
}

class ProcessInterfaceAdapterTest extends TestCase
{
    public function testProcess(): void
    {
        $worker = new Collect();

        $processor = new ProcessorInterfaceAdapter($worker);
        $message = new Message('body', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]);
        $processor->process($message, []);

        self::assertInstanceOf(ReadableMessage::class, $worker->lastProcessedMessages);
        self::assertSame('ponies.over.unicorns', $worker->lastProcessedMessages->getRoutingKey());
    }

    public function testProcessWithCustomDependencies(): void
    {
        $worker = new Collect();

        $bodyFactory = new Standard();
        $bodyFactory->handleContentType('application/x-custom', new \Puzzle\AMQP\Messages\TypedBodyFactories\Text());

        $processor = new ProcessorInterfaceAdapter($worker);
        $processor
            ->setEventDispatcher(new Symfony(new EventDispatcher()))
            ->setMessageAdapterFactory(new MessageAdapterFactory($bodyFactory));

        $message = new Message('body', [
            'content_type' => 'application/x-custom',
            'routing_key' => 'ponies.over.unicorns',
        ]);
        $processor->process($message, []);

        self::assertInstanceOf(ReadableMessage::class, $worker->lastProcessedMessages);
        self::assertSame('ponies.over.unicorns', $worker->lastProcessedMessages->getRoutingKey());
    }

    public function testOnConsume(): void
    {
        $worker = new Collect();

        $processor = new ProcessorInterfaceAdapter($worker);
        $processor->appendMessageProcessor(new ChangeBodyProcessor('pony'));

        $processor->process(new Message('horse', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]), []);

        self::assertSame('pony', $worker->lastProcessedMessages->getBodyInOriginalFormat());

        $processor->process(new Message('lamb', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]), []);

        self::assertSame('pony', $worker->lastProcessedMessages->getBodyInOriginalFormat());

        $processor->appendMessageProcessor(new ChangeBodyProcessor('unicorn'));

        $processor->process(new Message('donkey', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]), []);

        self::assertSame('pony', $worker->lastProcessedMessages->getBodyInOriginalFormat());

        $processor->setMessageProcessors([
            new ChangeBodyProcessor('pegasus'),
            new ChangeBodyProcessor('unicorn'),
            new ChangeBodyProcessor('pony'),
        ]);

        $processor->process(new Message('donkey', [
            'content_type' => ContentType::TEXT,
            'routing_key' => 'ponies.over.unicorns',
        ]), []);

        self::assertSame('pegasus', $worker->lastProcessedMessages->getBodyInOriginalFormat());
    }

    #[DataProvider('providerTestCatchingThrowable')]
    public function testCatchingThrowable(Worker $worker, $expectedException): void
    {
        $processor = new ProcessorInterfaceAdapter($worker);
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

        self::assertInstanceOf($expectedException, $current);
    }

    public static function providerTestCatchingThrowable(): array
    {
        return [
            'exception' => [
                'worker' => self::callableWorker(static function() {
                    throw new \Exception('Zboui zboui zboui');
                }),
                'expectedException' => \Exception::class,
            ],
            'error' => [
                'worker' => self::callableWorker(static function() {
                    throw new \Error('This is a PHP error');
                }),
                'expectedException' => \ErrorException::class,
            ],
        ];
    }

    private static function callableWorker($callable): CallableWorker
    {
        return new CallableWorker($callable);
    }
}
