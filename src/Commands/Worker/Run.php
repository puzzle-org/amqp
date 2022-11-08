<?php

namespace Puzzle\AMQP\Commands\Worker;

use Psr\Log\LoggerAwareTrait;
use Puzzle\AMQP\Workers\Worker;
use Swarrot\Processor\ProcessorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Puzzle\AMQP\Workers\ProcessorInterfaceAdapter;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Workers\WorkerProvider;
use Puzzle\Pieces\OutputInterfaceAware;
use Puzzle\Pieces\EventDispatcher\EventDispatcherAware;
use Puzzle\Pieces\EventDispatcher\NullEventDispatcher;
use Puzzle\AMQP\Workers\MessageAdapterFactoryAware;

class Run extends Command
{
    use
        LoggerAwareTrait,
        EventDispatcherAware,
        MessageAdapterFactoryAware;

    private
        $client,
        $outputInterfaceAware;

    private WorkerProvider
        $provider;

    public function __construct(Client $client,WorkerProvider $provider, OutputInterfaceAware $outputInterfaceAware)
    {
        parent::__construct();

        $this->client = $client;
        $this->provider = $provider;
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

        $output->writeln("Launching <info>$workerName</info>");

        $processor = $this->createProcessor(
            $this->provider->workerFor($workerName)
        );

        $consumer = $this->provider->consumerFor($workerName);
        $context = $this->provider->contextFor($workerName);

        $this->eventDispatcher->dispatch('worker.run');

        $consumer->setLogger($this->logger);
        $consumer->consume($processor, $this->client, $context->queueName());

        return Command::SUCCESS;
    }

    private function createProcessor(Worker $worker): ProcessorInterface
    {
        $processor = new ProcessorInterfaceAdapter($worker);
        
        $processor
            ->setEventDispatcher($this->eventDispatcher)
            ->setMessageAdapterFactory($this->messageAdapterFactory)
            ->setMessageProcessors(
                $this->provider->messageProcessors()
            );

        return $processor;
    }
}
