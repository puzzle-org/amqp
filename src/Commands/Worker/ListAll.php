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
    private
        $workerProvider;

    public function __construct(WorkerProvider $workerProvider)
    {
        parent::__construct();

        $this->workerProvider = $workerProvider;
    }

    protected function configure()
    {
        $this->setName('list')
             ->addArgument(
                  'workerNamePattern',
                  InputArgument::OPTIONAL,
                  'Regex pattern of the worker name',
                  null
             )
            ->setDescription('List AMQP workers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $workerNamePattern = $input->getArgument('workerNamePattern');

        if(!empty($workerNamePattern))
        {
            $comment = sprintf('List of worker with following pattern: %s', $workerNamePattern);
            $services = $this->workerProvider->listWithRegexFilter($workerNamePattern);
        }
        else
        {
            $comment = 'List of all workers';
            $services = $this->workerProvider->listAll();
        }

        $output->writeln(sprintf('<comment>%s</comment>', $comment));

        if(empty($services) || !is_array($services))
        {
            $output->writeln('<error>No worker found</error>');
            return 0;
        }

        ksort($services);

        $workerNameMaxLength = $this->getWorkerNameColumnSize($services);

        $style = new OutputFormatterStyle('cyan', null, array());
        $output->getFormatter()->setStyle('queue', $style);

        foreach($services as $name => $info)
        {
            $output->writeln(sprintf(
                "<info> %s</info>%s\n\t<queue>--> %s</queue>\n",
                str_pad($name, $workerNameMaxLength, ' '),
                $info['description'],
                $info['queue']
            ));
        }

        return 0;
    }

    private function getWorkerNameColumnSize(array $workers = array())
    {
        return max(array_map('strlen', array_keys($workers))) + 4;
    }
}
