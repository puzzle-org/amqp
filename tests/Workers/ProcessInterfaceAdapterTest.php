<?php

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Consumers\Simple;
use Psr\Log\LoggerAwareTrait;
use Swarrot\Broker\Message;
use Puzzle\AMQP\Messages\ContentType;

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
}
