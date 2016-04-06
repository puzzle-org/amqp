<?php

namespace Puzzle\AMQP\Workers;

interface WorkerProvider
{
    public function getWorker($workerName);

    public function listAll();
    
    public function listWithRegexFilter($workerNamePattern);
}
