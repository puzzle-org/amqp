<?php

namespace Puzzle\AMQP\Clients;

use Puzzle\Configuration;
use Puzzle\PrefixedConfiguration;

use Puzzle\AMQP\Client;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Clients\Processors\MessageProcessorAware;
use Puzzle\AMQP\Clients\MemoryManagementStrategies\NullMemoryManagementStrategy;

class Pecl implements Client
{
    use
        LoggerAwareTrait,
        MessageProcessorAware;

    const
        DEFAULT_PORT = 5672;

    private
        $applicationId,
        $configuration,
        $channel,
        $memoryManagementStrategy;

    public function __construct(Configuration $configuration)
    {
        $this->applicationId = $configuration->read('app/id', 'Unknown application');
        $this->configuration = $configuration;
        $this->channel = null;
        $this->memoryManagementStrategy = new NullMemoryManagementStrategy();
        $this->logger = new NullLogger();
    }

    public function setMemoryManagementStrategy(MemoryManagementStrategy $strategy)
    {
        $this->memoryManagementStrategy = $strategy;

        return $this;
    }

    private function ensureIsConnected()
    {
        if(! $this->channel instanceof \AMQPChannel)
        {
            $configuration = new PrefixedConfiguration($this->configuration, 'amqp/broker');

            // Create a connection
            $connection = new \AMQPConnection();
            $connection->setHost($configuration->readRequired('host'));
            $connection->setLogin($configuration->readRequired('login'));
            $connection->setPassword($configuration->readRequired('password'));
            $connection->setPort($configuration->read('port', self::DEFAULT_PORT));

            $vhost = $configuration->read('vhost', null);
            if($vhost !== null)
            {
                $connection->setVhost($vhost);
            }

            $connection->connect();

            // Create a channel
            $this->channel = new \AMQPChannel($connection);
        }
    }

    public function publish($exchangeName, WritableMessage $message)
    {
        if($message->isChunked())
        {
            $client = new ChunkedMessageClient($this, $this->memoryManagementStrategy);

            return $client->publish($exchangeName, $message);
        }

        try
        {
            $ex = $this->getExchange($exchangeName);
        }
        catch(\Exception $e)
        {
            $this->logMessage($exchangeName, $message);

            return false;
        }

        return $this->sendMessage($ex, $message);
    }

    private function logMessage($exchangeName, WritableMessage $message)
    {
        $log = json_encode(array(
            'exchange' => $exchangeName,
            'message' => (string) $message,
        ));

        $this->logger->error($log, ['This message was involved by an error (it was sent ... or not. Please check other logs)']);
    }

    private function sendMessage(\AMQPExchange $ex, WritableMessage $message)
    {
        try
        {
            $this->updateMessageAttributes($message);

            $ex->publish(
                $message->getBodyInTransportFormat(),
                $message->getRoutingKey(),
                $this->computeMessageFlag($message),
                $message->packAttributes()
            );
        }
        catch(\Exception $e)
        {
            $this->logMessage($ex->getName(), $message);

            return false;
        }

        return true;
    }

    private function computeMessageFlag(WritableMessage $message)
    {
        $flag = AMQP_NOPARAM;
        $disallowSilentDropping = $this->configuration->read('amqp/global/disallowSilentDropping', false);

        if($disallowSilentDropping === true || $message->canBeDroppedSilently() === false)
        {
            $flag = AMQP_MANDATORY;
        }

        return $flag;
    }

    public function getExchange($exchangeName = null, $type = AMQP_EX_TYPE_TOPIC)
    {
        $this->ensureIsConnected();

        $ex = new \AMQPExchange($this->channel);

        if(!empty($exchangeName))
        {
            $ex->setName($exchangeName);
            $ex->setType($type);
            $ex->setFlags(AMQP_PASSIVE);
            $ex->declareExchange();
        }

        return $ex;
    }

    private function updateMessageAttributes(WritableMessage $message)
    {
        $message->setAttribute('app_id', $this->applicationId);
        $message->addHeader('routing_key', $message->getRoutingKey());

        $this->onPublish($message);
    }

    public function getQueue($queueName)
    {
        $this->ensureIsConnected();

        $queue = new \AMQPQueue($this->channel);
        $queue->setName($queueName);

        return $queue;
    }
}
