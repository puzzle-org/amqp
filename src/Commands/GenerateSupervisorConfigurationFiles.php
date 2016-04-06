<?php

namespace Puzzle\AMQP\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Puzzle\AMQP\Workers\WorkerProvider;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Puzzle\AMQP\Services\SupervisorConfigurationGenerator;
use Puzzle\AMQP\Services\CommandGenerator;
use Symfony\Component\Console\Input\InputOption;
use Puzzle\Pieces\ExtraInformation;
use Puzzle\Pieces\PathManipulation;

class GenerateSupervisorConfigurationFiles extends Command
{
    use
        ExtraInformation,
        PathManipulation;

    private
        $appId,
        $workerProvider,
        $supervisorConfigurationGenerator,
        $commandGenerator;

    public function __construct(WorkerProvider $workerProvider, $appId, $rootPath)
    {
        parent::__construct();

        $this->workerProvider = $workerProvider;
        $this->commandGenerator = new CommandGenerator($rootPath);
        $this->appId = (string) $appId;
    }

    protected function configure()
    {
        $this
            ->setName('generate:supervisord:configuration')
            ->setDescription('Generate supervisord configuration for workers.')
            ->addOption('destination', null, InputOption::VALUE_REQUIRED, '[REQUIRED] The directory where to write the generated configuration file.')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, '[REQUIRED] The server to generate configuration files for. (ex: worker, server, etc.)')
            ->addOption('autostart', null, InputOption::VALUE_REQUIRED, 'Configure the autostart value. (default true)', 'true')
            ->addOption('autorestart', null, InputOption::VALUE_REQUIRED, 'Configure the autorestart value. (default true)', 'true')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Quiet mode.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startTimer();

        $this->process($input, $output);

        $this->endTimer();
        $this->outputExtraInformation($output);
    }

    private function process(InputInterface $input, OutputInterface $output)
    {
        $this->checkRequirements($input);
        $destination = $this->retrieveDestination($input, $output);
        $server = $this->retrieveServer($input, $output);

        $workers = $this->workerProvider->listAll();

        $autostart = $this->retrieveBoolean('autostart', $input);
        $autorestart = $this->retrieveboolean('autorestart', $input);

        $fs = new Filesystem(new Local($destination, true));
        $supervisorConfigurationGenerator = new SupervisorConfigurationGenerator($fs, $this->commandGenerator);
        $supervisorConfigurationGenerator->generate($workers, $autostart, $autorestart, $server, $this->appId, $destination, $output);
    }

    private function retrieveboolean($name, InputInterface $input)
    {
        $value = $input->getOption($name);

        if(! in_array($value, ['true', 'false']))
        {
            throw new \InvalidArgumentException(sprintf('Invalid value "%s" for option %s, expecting boolean.', $value, $name));
        }

        return $value === 'true';
    }

    private function checkRequirements(InputInterface $input)
    {
        $destination = $input->getOption('destination');
        if(empty($destination))
        {
            throw new \InvalidArgumentException('The option --destination is required.');
        }

        $server = $input->getOption('server');
        if(empty($server))
        {
            throw new \InvalidArgumentException('The option --server is required.');
        }
    }

    private function retrieveDestination(InputInterface $input, OutputInterface $output)
    {
        $destination = $this->enforceEndingSlash($input->getOption('destination'));

        if( ! $input->getOption('quiet'))
        {
            $output->writeln(sprintf('<info>Destination : <comment>%s</comment></info>', $destination));
        }

        return $destination;
    }

    private function retrieveServer(InputInterface $input, OutputInterface $output)
    {
        $server = $input->getOption('server');

        if( ! $input->getOption('quiet'))
        {
            $output->writeln(sprintf('<info>Server : <comment>%s</comment></info>', $server));
        }

        return $server;
    }
}
