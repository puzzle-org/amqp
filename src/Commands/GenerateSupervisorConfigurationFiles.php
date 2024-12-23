<?php

declare(strict_types = 1);

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

    private string
        $appId;
    private WorkerProvider
        $workerProvider;
    private CommandGenerator
        $commandGenerator;

    public function __construct(WorkerProvider $provider, string $appId)
    {
        parent::__construct();

        $this->appId = $appId;
        $this->workerProvider = $provider;
        $this->commandGenerator = new CommandGenerator();
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:supervisord:configuration')
            ->setDescription('Generate supervisord configuration for workers.')
            ->addOption('destination', null, InputOption::VALUE_REQUIRED, '[REQUIRED] The directory where to write the generated configuration file.')
            ->addOption('autostart', null, InputOption::VALUE_REQUIRED, 'Configure the autostart value. (default true)', 'true')
            ->addOption('autorestart', null, InputOption::VALUE_REQUIRED, 'Configure the autorestart value. (default true)', 'true')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Quiet mode.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();

        $this->process($input, $output);

        $this->endTimer();
        $this->outputExtraInformation($output);

        return Command::SUCCESS;
    }

    private function process(InputInterface $input, OutputInterface $output): void
    {
        $this->checkRequirements($input);
        $destination = $this->retrieveDestination($input, $output);

        $workers = $this->workerProvider->listAll();

        $autostart = $this->retrieveBoolean('autostart', $input);
        $autorestart = $this->retrieveboolean('autorestart', $input);

        $fs = new Filesystem(new Local($destination, true));
        $supervisorConfigurationGenerator = new SupervisorConfigurationGenerator($fs, $this->commandGenerator);
        $supervisorConfigurationGenerator->generate($workers, $autostart, $autorestart, $this->appId, $destination, $output);
    }

    private function retrieveboolean(string $name, InputInterface $input): bool
    {
        $value = $input->getOption($name);

        if(! in_array($value, ['true', 'false']))
        {
            throw new \InvalidArgumentException(sprintf('Invalid value "%s" for option %s, expecting boolean.', $value, $name));
        }

        return $value === 'true';
    }

    private function checkRequirements(InputInterface $input): void
    {
        $destination = $input->getOption('destination');
        if(empty($destination))
        {
            throw new \InvalidArgumentException('The option --destination is required.');
        }
    }

    private function retrieveDestination(InputInterface $input, OutputInterface $output): string
    {
        $destination = $this->enforceEndingSlash($input->getOption('destination'));

        if( ! $input->getOption('quiet'))
        {
            $output->writeln(sprintf('<info>Destination : <comment>%s</comment></info>', $destination));
        }

        return $destination;
    }
}
