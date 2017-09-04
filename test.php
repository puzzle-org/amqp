<?php

require 'vendor/autoload.php';

use Puzzle\Configuration\Yaml;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Messages\Processors\GZip;
use Puzzle\AMQP\Clients\MemoryManagementStrategy;
use Puzzle\AMQP\Messages\Chunks\FileChunkedMessage;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessage;
use Puzzle\AMQP\Clients\ChunkedMessageClient;

$configuration = new Yaml(new Filesystem(new Local(__DIR__ . '/config')));
$client = new Pecl($configuration);
$client->appendMessageProcessor(new GZip());

class GCTrigger implements MemoryManagementStrategy
{
    private
        $threshold;

    public function init(ChunkedMessage $message)
    {
        $this->threshold = (100 * 1000 * 1000) / $message->getChunkSize();
    }

    public function manage($iteration)
    {
        if($iteration % $this->threshold === 0)
        {
            gc_collect_cycles();
        }
    }
}

$chunkSize = 10 * 1000 * 1000;
$message = new FileChunkedMessage('media.fau.xml', 'FAU.xml', $chunkSize);
//$message->allowCompression();
$message->setContentType('application/xml');

$splitter = new ChunkedMessageClient($client, new GCTrigger());
$splitter->publish('puzzle', $message);
