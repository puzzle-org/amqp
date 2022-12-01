<?php

namespace Puzzle\AMQP\Consumers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;

class InstantRetry extends AbstractConsumer
{
    private ?int
        $retries,
        $delay;

    public function __construct(?int $retries = null, ?int $delayInSeconds = null)
    {
        parent::__construct();

        $this->retries = $retries;
        $this->delay = $delayInSeconds;
    }

    public function consume(ProcessorInterface $processor, Client $client, string $queue): void
    {
        parent::consume($processor, $client, $queue);

        $stack = $this->getBaseStack()
            ->push('Swarrot\Processor\MaxExecutionTime\MaxExecutionTimeProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $this->messageProvider, $this->logger)
            ->push('Swarrot\Processor\InstantRetry\InstantRetryProcessor', $this->logger)
        ;

        $consumer = $this->getSwarrotConsumer($stack);

        $consumer->consume($this->options());
    }

    private function options(): array
    {
        $options = [
            'max_execution_time' => self::DEFAULT_MAX_EXECUTION_TIME,
        ];

        if(!empty($this->retries))
        {
            $options['instant_retry_attempts'] = $this->retries;
        }

        if(!empty($this->delay))
        {
            $options['instant_retry_delay'] = $this->delay * 1000000; //computed in microseconds
        }

        return $options;
    }
}
