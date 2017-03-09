<?php

namespace Puzzle\AMQP\Silex;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Workers\Providers\Pimple;
use Puzzle\AMQP\Consumers;
use Puzzle\AMQP\Subscribers\ManagedConnection as ManagedConnectionSubscribers;

class AmqpServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $this->registerAmqpServices($app);
        $this->registerConsumers($app);
        $this->registerManagedConnectionHandlers($app);
    }

    private function registerAmqpServices(Container $app)
    {
        $app['amqp.client'] = function($c) {
            return new Pecl($c['configuration']);
        };

        $app['amqp.workerProvider'] = function($c) {
            return new Pimple($c);
        };
    }

    private function registerConsumers(Container $app)
    {
        $app['amqp.consumers.simple'] = function() {
            return new Consumers\Simple();
        };

        $app['amqp.consumers.insomniac'] = function() {
            return new Consumers\Insomniac();
        };

        $app['amqp.consumers.retry'] = $app->protect(function(Container $c, $retries = null) {
            return new Consumers\Retry($retries);
        });

        $app['amqp.consumers.instantRetry'] = $app->protect(function(Container $c, $retries, $delayInSeconds) {
            return new Consumers\InstantRetry($retries, $delayInSeconds);
        });
    }

    private function registerManagedConnectionHandlers(Container $app)
    {
        $app['managed.connection.handler.mysql'] = $app->protect(function(\Doctrine\DBAL\Driver\Connection $mysqlConnection) use($app) {
            $app['managed.connection.dispatcher']->addSubscriber(new ManagedConnectionSubscribers\Mysql($mysqlConnection));
        });

        $app['managed.connection.handler.redis'] = $app->protect(function(\Predis\Client $redisConnection) use($app) {
            $app['managed.connection.dispatcher']->addSubscriber(new ManagedConnectionSubscribers\Redis($redisConnection));
        });

        $app['managed.connection.handler.mongoDb'] = $app->protect(function(\MongoClient $mongoDbConnection) use($app) {
            $app['managed.connection.dispatcher']->addSubscriber(new ManagedConnectionSubscribers\MongoDb($mongoDbConnection));
        });
    }
}
