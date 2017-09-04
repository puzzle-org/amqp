<?php

require 'vendor/autoload.php';

use Puzzle\Configuration\Yaml;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Messages\Processors\GZip;
use Puzzle\AMQP\Workers\Chunks\ChunkAssembler;
use Puzzle\AMQP\Workers\WorkerContext;
use Puzzle\AMQP\Workers\ProcessorInterfaceAdapter;
use Puzzle\AMQP\Consumers\Insomniac;
use Puzzle\AMQP\Workers\Chunks\ChunkAssemblyTrackers\SingleTrackingInMemory;
use Puzzle\AMQP\Workers\Chunks\ChunkAssemblyStorages\FileStorage;
use Puzzle\AMQP\Workers\Chunks\ChunkAssemblyTrackers\MultipleTracker;
use Puzzle\AMQP\Consumers\Simple;

$configuration = new Yaml(new Filesystem(new Local(__DIR__ . '/config')));
$client = new Pecl($configuration);
$client->appendMessageProcessor(new GZip());

$consumer = new Insomniac();

$tracker = new MultipleTracker(function () {
    return new SingleTrackingInMemory();
});

$workerContext = new WorkerContext(function() use($tracker, $configuration) {
        return new ChunkAssembler($tracker, new FileStorage($configuration->read('var.path', 'var/')));
    },
    $consumer,
    'test_1'
);

$consumer->consume(
    new ProcessorInterfaceAdapter($workerContext),
    new Pecl($configuration),
    $workerContext
);
