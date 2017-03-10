<?php

namespace Puzzle\AMQP\Consumers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;

class Retry extends AbstractConsumer
{
    const
        DEFAULT_RETRY_OCCURENCE = 3,
        DEFAULT_RETRY_HEADER = 'swarrot_retry_attempts',
        RETRY_EXCHANGE_NAME = 'retry',
        RETRY_ROUTING_KEY_PATTERN = '%s_retry_%%attempt%%';

    private
        $retries;

    public function __construct($retries = null)
    {
        parent::__construct();

        $this->retries = $retries;
    }

    public function consume(ProcessorInterface $processor, Client $client, WorkerContext $workerContext)
    {
        parent::consume($processor, $client, $workerContext);

        $messagePublisher = new PeclPackageMessagePublisher($client->getExchange(self::RETRY_EXCHANGE_NAME));

        $stack = $this->getBaseStack()
            ->push('Swarrot\Processor\MaxExecutionTime\MaxExecutionTimeProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $this->messageProvider, $this->logger)
            ->push('Swarrot\Processor\Retry\RetryProcessor', $messagePublisher, $this->logger)
        ;

        $options = $this->getOptions($workerContext);

        $consumer = $this->getSwarrotConsumer($stack);

        return $consumer->consume($options);
    }

    private function getOptions(WorkerContext $workerContext)
    {
        $options = array(
            'max_execution_time' => self::DEFAULT_MAX_EXECUTION_TIME,
            'retry_key_pattern' => sprintf(self::RETRY_ROUTING_KEY_PATTERN, $workerContext->queue),
        );

        if(! empty($this->retries))
        {
            $options['retry_attempts'] = $this->retries;
        }

        return $options;
    }
}
