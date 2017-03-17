<?php

namespace Puzzle\AMQP\Workers\Providers;

use Pimple\Container;
use Puzzle\AMQP\Workers\WorkerContext;
use Puzzle\AMQP\Consumers\Simple;
use Puzzle\AMQP\Messages\Processors\NullProcessor;
use Puzzle\AMQP\Messages\Processor;
use Puzzle\AMQP\Workers\WorkerProvider;

class PimpleTest extends \PHPUnit_Framework_TestCase
{
    private
        $container;

    protected function setUp()
    {
        $this->container = new Container();
        $this->container['worker.pony'] = function () {
            return new WorkerContext(function (){}, new Simple(), 'some_queue');
        };
        $this->container['worker.closure.butWithoutContext'] = function () {
            new \StdClass;
        };
        $this->container['worker.instance.butWithoutContext'] = new \StdClass;
        $this->container['badPrefix.worker.unicorn'] = function () {
            return new WorkerContext(function (){}, new Simple(), 'some_queue');
        };
        $this->container['worker.pegasus.with.wings'] = function () {
            $context = new WorkerContext(function (){}, new Simple(), 'zodiac');
            $context->setDescription('Do nothing');

            return $context;
        };
        
        $this->container[WorkerProvider::MESSAGE_PROCESSORS_SERVICE_KEY] = function () {
            return [
                new NullProcessor(),
                new NullProcessor(),
            ];
        };
    }

    public function testListAll()
    {
        $provider = new Pimple($this->container);
        $list = $provider->listAll();

        $this->assertCount(2, $list);
        $this->assertArrayHasKey('pony', $list);
        $this->assertArrayHasKey('pegasus.with.wings', $list);

        $this->assertSame('some_queue', $list['pony']['queue']);

        $this->assertSame('zodiac', $list['pegasus.with.wings']['queue']);
        $this->assertSame('Do nothing', $list['pegasus.with.wings']['description']);
    }

    public function testWithRegexFilter()
    {
        $provider = new Pimple($this->container);
        $list = $provider->listWithRegexFilter('pon.*');

        $this->assertCount(1, $list);
        $this->assertArrayHasKey('pony', $list);
    }

    public function testGetWorker()
    {
        $provider = new Pimple($this->container);
        $context = $provider->getWorker('pony');

        $this->assertTrue($context instanceof WorkerContext);
        $this->assertSame('some_queue', $context->getQueueName());
    }
    
    public function testGetMessageProcessors()
    {
        $provider = new Pimple($this->container);
        $processors = $provider->getMessageProcessors();
        
        $this->assertTrue(is_array($processors));
        $this->assertCount(2, $processors);
        
        foreach($processors as $processor)
        {
            $this->assertTrue($processor instanceof Processor);
        }
    }
    
    public function testGetMessageProcessorsWhileNotDefined()
    {
        $provider = new Pimple(new Container());
        $processors = $provider->getMessageProcessors();
        
        $this->assertTrue(is_array($processors));
        $this->assertEmpty($processors);
    }
}
