<?php

namespace Puzzle\AMQP\Workers;

interface WorkerProvider
{
    const
        MESSAGE_PROCESSORS_SERVICE_KEY = 'amqp.messageProcessors';
    
    /**
     * @return \Puzzle\AMQP\Workers\WorkerContext
     */
    public function getWorker($workerName);

    /**
     * @return array
     */
    public function listAll();
    
    /**
     * @return array
     */
    public function listWithRegexFilter($workerNamePattern);
    
    /**
     * @return array
     */
    public function getMessageProcessors();
}
