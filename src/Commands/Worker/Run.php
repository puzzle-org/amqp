<?php

namespace Puzzle\AMQP\Commands\Worker;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
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
use Puzzle\AMQP\Events\WorkerRun;

class Run extends Command
{
    use
        LoggerAwareTrait,
        EventDispatcherAware,
        MessageAdapterFactoryAware;

    private Client
        $client;
    private OutputInterfaceAware
        $outputInterfaceAware;
    private WorkerProvider
        $provider;

    public function __construct(Client $client, WorkerProvider $provider, OutputInterfaceAware $outputInterfaceAware)
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

        try
        {
            $worker = $this->provider->workerFor($workerName);
        }
        catch(\Exception $e)
        {
            $output->writeln("<error>Can't retrieve Worker \"$workerName\"</error>");
            $output->writeln(sprintf('Error: <error>"%s"</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $processor = $this->createProcessor($worker);

        $consumer = $this->provider->consumerFor($workerName);
        $context = $this->provider->contextFor($workerName);

        $this->eventDispatcher->dispatch(WorkerRun::NAME);

        if($this->logger instanceof LoggerInterface)
        {
            $consumer->setLogger($this->logger);
        }
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
