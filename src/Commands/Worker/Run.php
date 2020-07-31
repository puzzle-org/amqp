<?php

namespace Puzzle\AMQP\Commands\Worker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Puzzle\AMQP\Workers\ProcessorInterfaceAdapter;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerProvider;
use Puzzle\AMQP\Workers\WorkerContext;
use Puzzle\Pieces\OutputInterfaceAware;
use Puzzle\Pieces\EventDispatcher\EventDispatcherAware;
use Puzzle\Pieces\EventDispatcher\NullEventDispatcher;
use Puzzle\AMQP\Workers\MessageAdapterFactoryAware;

class Run extends Command
{
    use
        EventDispatcherAware,
        MessageAdapterFactoryAware;

    private
        $client,
        $workerProvider,
        $outputInterfaceAware;

    public function __construct(Client $client, WorkerProvider $workerProvider, OutputInterfaceAware $outputInterfaceAware)
    {
        parent::__construct();

        $this->client = $client;
        $this->workerProvider = $workerProvider;
        $this->outputInterfaceAware = $outputInterfaceAware;
        
        $this->eventDispatcher = new NullEventDispatcher();
        $this->messageAdapterFactory = null;
    }

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Launch AMQP worker')
            ->addArgument('task', InputArgument::REQUIRED, 'worker name to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputInterfaceAware->register($output);

        $workerName = $input->getArgument('task');
        $workerContext = $this->workerProvider->getWorker($workerName);

        if($workerContext instanceof WorkerContext)
        {
            $output->writeln("Launching <info>$workerName</info>");

            $processor = $this->createProcessor($workerContext);

            $this->eventDispatcher->dispatch('worker.run');

            $workerContext->getConsumer()->consume($processor, $this->client, $workerContext);

            return 0;
        }

        $output->writeln("<error>Worker $workerName not found</error>");

        return 0;
    }

    private function createProcessor(WorkerContext $workerContext)
    {
        $processor = new ProcessorInterfaceAdapter($workerContext);
        
        $processor
            ->setEventDispatcher($this->eventDispatcher)
            ->setMessageAdapterFactory($this->messageAdapterFactory)
            ->setMessageProcessors(
                $this->workerProvider->getMessageProcessors()
            );

        return $processor;
    }
}
