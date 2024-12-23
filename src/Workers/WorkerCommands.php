<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

use Symfony\Component\Console\Application;
use Puzzle\AMQP\Commands\Worker\Run;
use Puzzle\AMQP\Commands\Worker\ListAll;
use Puzzle\AMQP\Client;
use Puzzle\Pieces\OutputInterfaceAware;
use Puzzle\Pieces\EventDispatcher\EventDispatcherAware;
use Puzzle\Pieces\EventDispatcher\NullEventDispatcher;

class WorkerCommands
{
    use
        EventDispatcherAware,
        MessageAdapterFactoryAware;

    private Application
        $console;
    private Client
        $client;
    private WorkerProvider
        $workerProvider;
    private OutputInterfaceAware
        $outputInterfaceAware;

    public function __construct(Application $console, Client $client, WorkerProvider $workerProvider, OutputInterfaceAware $outputInterfaceAware)
    {
        $this->console = $console;
        $this->client = $client;
        $this->workerProvider = $workerProvider;
        $this->outputInterfaceAware = $outputInterfaceAware;
        
        $this->messageAdapterFactory = null;
        $this->eventDispatcher = new NullEventDispatcher();
    }

    public function register(): void
    {
        $run = new Run(
            $this->client,
            $this->workerProvider,
            $this->outputInterfaceAware
        );
        $run
            ->setEventDispatcher($this->eventDispatcher)
            ->setMessageAdapterFactory($this->messageAdapterFactory);

        $this->console->add($run);

        $this->console->add(new ListAll(
            $this->workerProvider
        ));
    }
}
