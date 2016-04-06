<?php

namespace Puzzle\AMQP\Clients;

use Puzzle\Configuration;
use Puzzle\PrefixedConfiguration;

use Puzzle\AMQP\Client;
use Puzzle\AMQP\Messages;
use Puzzle\AMQP\Workers\MessageAdapter;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Collections;

class Pecl implements Client
{
    use LoggerAwareTrait;

    private
        $applicationId,
        $configuration,
        $channel;

    public function __construct(Configuration $configuration)
    {
        $this->applicationId = $configuration->readRequired('app/id');
        $this->configuration = $configuration;
        $this->channel = null;
        $this->logger = new NullLogger();
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
        try
        {
            $ex = $this->getExchange($exchangeName);
        }
        catch (\Exception $e)
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
                $message->getFormattedBody(),
                $message->getRoutingKey(),
                $message->getFlags(),
                $message->packAttributes()
            );
        }
        catch (\Exception $e)
        {
            $this->logMessage($ex->getName(), $message);

            return false;
        }

        return true;
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
        $message->setAttribute('app_id', $this->configuration->read('app/id', null));
        $message->addHeader('routing_key', $message->getRoutingKey());
    }

    public function getQueue($queueName)
    {
        $this->ensureIsConnected();

        $queue = new \AMQPQueue($this->channel);
        $queue->setName($queueName);

        return $queue;
    }

    private function convertEnvelopeToMessage(\AMQPEnvelope $envelope)
    {
        return new MessageAdapter(
            new \Swarrot\Broker\Message(
                $envelope->getBody(),
                array(
                    'content_type'      => $envelope->getContentType(),
                    'routing_key'       => $envelope->getRoutingKey(),
                    'delivery_tag'      => $envelope->getDeliveryTag(),
                    'delivery_mode'     => $envelope->getDeliveryMode(),
                    'exchange_name'     => $envelope->getExchangeName(),
                    'is_redelivery'     => $envelope->isRedelivery(),
                    'content_encoding'  => $envelope->getContentEncoding(),
                    'type'              => $envelope->getType(),
                    'timestamp'         => $envelope->getTimeStamp(),
                    'priority'          => $envelope->getPriority(),
                    'expiration'        => $envelope->getExpiration(),
                    'app_id'            => $envelope->getAppId(),
                    'message_id'        => $envelope->getMessageId(),
                    'reply_to'          => $envelope->getReplyTo(),
                    'correlation_id'    => $envelope->getCorrelationId(),
                    'headers'           => $envelope->getHeaders(),
                    'user_id'           => $envelope->getUserId(),
                    'cluster_id'        => 0,
                    'channel'           => '',
                    'consumer_tag'      => ''
                ),
                $envelope->getDeliveryTag()
            )
        );
    }
}
