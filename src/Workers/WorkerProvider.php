<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

use Puzzle\AMQP\Consumer;

interface WorkerProvider
{
    public const
        MESSAGE_PROCESSORS_SERVICE_KEY = 'amqp.messageProcessors';

    public function contextFor(string $workerName): WorkerContext;

    public function consumerFor(string $workerName): Consumer;

    public function workerFor(string $workerName): Worker;

    public function listAll(): array;

    /**
     * @return \Puzzle\AMQP\Messages\Processor[]
     */
    public function messageProcessors(): array;
}
