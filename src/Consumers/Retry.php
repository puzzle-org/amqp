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

    private ?int
        $retries;

    public function __construct(?int $retries = null)
    {
        parent::__construct();

        $this->retries = $retries;
    }

    public function consume(ProcessorInterface $processor, Client $client, string $queue): void
    {
        parent::consume($processor, $client, $queue);

        $messagePublisher = new PeclPackageMessagePublisher($client->getExchange(self::RETRY_EXCHANGE_NAME));

        $stack = $this->getBaseStack()
            ->push('Swarrot\Processor\MaxExecutionTime\MaxExecutionTimeProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $this->messageProvider, $this->logger)
            ->push('Swarrot\Processor\Retry\RetryProcessor', $messagePublisher, $this->logger)
        ;

        $consumer = $this->getSwarrotConsumer($stack);

        $consumer->consume(
            $this->options($queue)
        );
    }

    private function options(string $queue): array
    {
        $options = [
            'max_execution_time' => self::DEFAULT_MAX_EXECUTION_TIME,
            'retry_key_pattern' => sprintf(self::RETRY_ROUTING_KEY_PATTERN, $queue),
        ];

        if(! empty($this->retries))
        {
            $options['retry_attempts'] = $this->retries;
        }

        return $options;
    }
}
