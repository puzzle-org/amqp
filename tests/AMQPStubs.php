<?php

if(! class_exists(\AMQPQueue::class))
{
    class AMQPQueue
    {
        private ?string
            $name;

        public function __construct(?AMQPChannel $channel = null)
        {
            $this->name = null;
        }

        public function setName(string $name): void
        {
            $this->name = $name;
        }

        public function getName(): ?string
        {
            return $this->name;
        }
    }
}

if(! class_exists(\AMQPExchange::class))
{
    class AMQPExchange
    {
        private ?string
            $name;

        public function __construct(?AMQPChannel $channel = null)
        {
            $this->name = null;
        }

        public function setName(string $name): void
        {
            $this->name = $name;
        }

        public function getName(): ?string
        {
            return $this->name;
        }
    }
}

if(! class_exists(\AMQPChannel::class))
{
    class AMQPChannel
    {
    }
}
