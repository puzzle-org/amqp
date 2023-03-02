<?php

namespace Puzzle\AMQP\Commands\Worker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Puzzle\AMQP\Workers\WorkerProvider;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ListAll extends Command
{
    private WorkerProvider
        $provider;

    public function __construct(WorkerProvider $provider)
    {
        parent::__construct();

        $this->provider = $provider;
    }

    protected function configure()
    {
        $this->setName('list')
            ->setDescription('List AMQP workers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>List of all workers</comment>');

        $services = $this->provider->listAll();

        if(empty($services))
        {
            $output->writeln('<error>No worker found</error>');
            return 0;
        }

        ksort($services);

        $workerNameMaxLength = $this->getWorkerNameColumnSize($services);

        $style = new OutputFormatterStyle('cyan', null, array());
        $output->getFormatter()->setStyle('queue', $style);

        foreach($services as $name => $description)
        {
            $output->writeln(sprintf(
                "<info> %s</info>%s",
                str_pad($name, $workerNameMaxLength, ' '),
                $description,
            ));
        }

        return 0;
    }

    private function getWorkerNameColumnSize(array $workers = array())
    {
        return max(array_map('strlen', array_keys($workers))) + 4;
    }
}
