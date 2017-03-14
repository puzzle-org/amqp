<?php

namespace Puzzle\AMQP\Workers\Providers;

use Puzzle\AMQP\Workers\WorkerProvider;
use Puzzle\AMQP\Workers\WorkerContext;

class Pimple implements WorkerProvider
{
    private
        $container;

    public function __construct(\Pimple\Container $container)
    {
        $this->container = $container;
    }

    public function getWorker($workerName)
    {
        $workerContext = null;
        $key = $this->computeWorkerServiceKey($workerName);

        if(isset($this->container[$key]))
        {
            $workerContext = $this->container[$key];
        }

        return $workerContext;
    }

    private function computeWorkerServiceKey($workerName)
    {
        return sprintf(
            'worker.%s',
            $workerName
        );
    }

    public function listAll()
    {
        $workers = $this->extractWorkers();

        return $this->listWorkers($workers);
    }

    public function listWithRegexFilter($workerNamePattern)
    {
        $workers = $this->extractWorkers();
        $workers = new \RegexIterator(new \ArrayIterator($workers), sprintf('~^worker\.%s~', $workerNamePattern));
        $workers = iterator_to_array($workers);

        return $this->listWorkers($workers);
    }

    private function listWorkers(array $extractedWorkers)
    {
        $workers = array();

        foreach($extractedWorkers as $worker)
        {
            $key = $this->formatWorkerName($worker);
            $worker = $this->container[$worker];

            if($worker instanceof WorkerContext)
            {
                $workers[$key] = [
                    'queue' => $worker->getQueue(),
                    'description' => $worker->getDescription(),
                ];
            }
        }

        return $workers;
    }

    private function extractWorkers()
    {
        $services = new \ArrayIterator($this->container->keys());
        $services = new \RegexIterator($services, '~^worker\..+~');

        return iterator_to_array($services);
    }

    private function formatWorkerName($workerServiceName)
    {
        return str_replace('worker.', '', $workerServiceName);
    }
}
