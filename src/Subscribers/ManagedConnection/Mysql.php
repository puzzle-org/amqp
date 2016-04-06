<?php

namespace Puzzle\AMQP\Subscribers\ManagedConnection;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\DBAL\Connection;

class Mysql implements EventSubscriberInterface
{
    private
        $mysqlDb;

    public function __construct(Connection $mysqlDb)
    {
        $this->mysqlDb = $mysqlDb;
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
        $this->disconnectMysql();
    }

    public function onWorkerProcessed()
    {
        $this->disconnectMysql();
    }

    private function disconnectMysql()
    {
        $this->mysqlDb->close();
    }
}
