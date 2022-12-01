<?php

namespace Puzzle\AMQP\Subscribers\ManagedConnection;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Events\WorkerProcessed;
use Puzzle\AMQP\Events\WorkerRun;

class Redis implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    
    private
        $redisClient;
    
    public function __construct(\Predis\Client $redisClient)
    {
        $this->redisClient = $redisClient;
    }

    public static function getSubscribedEvents()
    {
        return array(
            WorkerRun::NAME => array('onWorkerRun'),
            WorkerProcessed::NAME => array('onWorkerProcessed'),
        );
    }
    
    public function onWorkerRun(Event $event)
    {
        $this->disconnectRedis();
    }
    
    public function onWorkerProcessed()
    {
        $this->disconnectRedis();
    }
    
    private function disconnectRedis()
    {
        $this->redisClient->disconnect();
    }
}
