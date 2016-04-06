<?php

namespace Puzzle\AMQP\Consumers;

use Swarrot\Processor\ProcessorInterface;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerContext;

class InstantRetry extends AbstractConsumer
{
    private
        $retries,
        $delay;

    public function __construct($retries = null, $delayInSeconds = null)
    {
        parent::__construct();

        $this->retries = $retries;
        $this->delay = $delayInSeconds;
    }

    public function consume(ProcessorInterface $processor, Client $client, WorkerContext $workerContext)
    {
        parent::consume($processor, $client, $workerContext);

        $stack = $this->getBaseStack()
            ->push('Swarrot\Processor\MaxExecutionTime\MaxExecutionTimeProcessor', $this->logger)
            ->push('Swarrot\Processor\Ack\AckProcessor', $this->messageProvider, $this->logger)
            ->push('Swarrot\Processor\InstantRetry\InstantRetryProcessor', $this->logger)
        ;

        $options = $this->getOptions();

        $consumer = $this->getSwarrotConsumer($stack);

        return $consumer->consume($options);
    }

    private function getOptions()
    {
        $options = array(
            'max_execution_time' => self::DEFAULT_MAX_EXECUTION_TIME,
        );

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