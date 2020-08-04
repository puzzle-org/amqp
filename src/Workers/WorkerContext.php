<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

final class WorkerContext
{
    private string
        $name,
        $queueName,
        $description;

    private ?string
        $consumerServiceId;

    public function __construct(string $name, string $queueName, string $description, ?string $consumerServiceId = null)
    {
        if(empty($name))
        {
            throw new \LogicException("Empty worker name");
        }

        if(empty($queueName))
        {
            throw new \LogicException("Empty queue name");
        }

        $this->name = $name;
        $this->queueName = $queueName;
        $this->description = $description;
        $this->consumerServiceId = $consumerServiceId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function queueName(): string
    {
        return $this->queueName;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function consumerServiceId(): ?string
    {
        return $this->consumerServiceId;
    }
}
