<?php

namespace Puzzle\AMQP\Subscribers\ManagedConnection;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MongoDb implements EventSubscriberInterface
{
    private
        $mongoDbClient;

    public function __construct(\MongoClient $mongoDbClient)
    {
        $this->mongoDbClient = $mongoDbClient;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'worker.run' => array('onWorkerRun'),
            'worker.processed' => array('onWorkerProcessed'),
        );
    }

    public function onWorkerRun(Event $event)
    {
        $this->disconnectMongoDb();
    }

    public function onWorkerProcessed()
    {
        $this->disconnectMongoDb();
    }

    private function disconnectMongoDb()
    {
        $this->mongoDbClient->close();
    }
}
