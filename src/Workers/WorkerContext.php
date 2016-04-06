<?php

namespace Puzzle\AMQP\Workers;

use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Consumer;
use Psr\Log\LoggerInterface;
use Puzzle\AMQP\Collections\MessageHookCollection;

class WorkerContext
{
    use LoggerAwareTrait;
    
    const
        INSTANCES_DEFAULT_VALUE = 1,
        SERVER_DEFAULT_VALUE = 'worker',
        IS_DEPLOYMENT_ALLOWED = true;

    public
        $queue,
        $description,
        $instances,
        $servers,
        $isDeploymentAllowed;
    
    private
        $consumer,
        $worker,
        $messageHooks;

    public function __construct(\Closure $worker, Consumer $consumer, $queue)
    {
        $this->worker = $worker;
        $this->consumer = $consumer;
        $this->queue = $queue;
        $this->instances = self::INSTANCES_DEFAULT_VALUE;
        $this->servers = [self::SERVER_DEFAULT_VALUE];
        $this->isDeploymentAllowed = true;
    }

    public function getWorker()
    {
        if($this->worker instanceof \Closure)
        {
            $this->worker = $this->worker($this->dic);
            $this->worker->setLogger($this->logger);
        }
    
        return $this->worker;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->consumer->setLogger($logger);

        return $this;
    }
    
    public function getLogger()
    {
        return $this->logger;
    }
    
    public function getConsumer()
    {
        return $this->consumer;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getQueue()
    {
        return $this->queue;
    }
    
    public function setMessageHooks(MessageHookCollection $messageHookCollection)
    {
        $this->messageHooks = $messageHookCollection;
    }
    
    public function getMessageHooks()
    {
        return $this->messageHooks;
    }
    
    public function deployInstances($numberOfInstance)
    {
        if(! empty($numberOfInstance))
        {
            $this->instances = (int) $numberOfInstance;
        }
        
        return $this;
    }
    
    public function getInstances()
    {
        return $this->instances;
    }
    
    public function deployOn($servers)
    {
        if(! empty($servers))
        {
            if(! is_array($servers))
            {
                $servers = [$servers];
            }
            
            $this->servers = $servers;
        }
    
        return $this;
    }
    
    public function getServers()
    {
        return $this->servers;
    }
    
    public function disableDeployment()
    {
        $this->isDeploymentAllowed = false;
    
        return $this;
    }
    
    public function isDeploymentAllowed()
    {
        return $this->isDeploymentAllowed;
    }
}