<?php

require 'vendor/autoload.php';

use Puzzle\Configuration\Yaml;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Messages\Processors\GZip;
use Puzzle\AMQP\Clients\MemoryManagementStrategy;
use Puzzle\AMQP\Clients\ChunkedMessageClient;
use Puzzle\AMQP\Messages\Message;

$configuration = new Yaml(new Filesystem(new Local(__DIR__ . '/config')));
$client = new Pecl($configuration);
$client->appendMessageProcessor(new GZip());

class GCTrigger implements MemoryManagementStrategy
{
    private
        $usedMemory,
        $threshold;

    public function init()
    {
        $this->threshold = (100 * 1000 * 1000);
        $this->usedMemory = 0;
    }

    public function manage($sentSize)
    {
        $this->usedMemory += $sentSize;

        if($this->usedMemory >= $this->threshold)
        {
            gc_collect_cycles();
            $this->usedMemory = 0;
        }
    }
}

$chunkSize = 10 * 1000 * 1000;
$message = new Message('media.fau.xml');
$message->setStreamedFile('FAU.xml', $chunkSize);
//$message->allowCompression();

$splitter = new ChunkedMessageClient($client, new GCTrigger());
$splitter->publish('puzzle', $message);
