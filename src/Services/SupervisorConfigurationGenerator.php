<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Services;

use Gaufrette\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

class SupervisorConfigurationGenerator
{
    private bool
        $overwrite;
    private Filesystem
        $filesystem;
    private CommandGenerator
        $commandGenerator;

    public function __construct(Filesystem $filesystem, CommandGenerator $commandGenerator)
    {
        $this->filesystem = $filesystem;
        $this->commandGenerator = $commandGenerator;
        $this->overwrite = true;
    }

    public function generate(array $workers, bool $autostart, bool $autorestart, string $appId, string $destination, OutputInterface $output): void
    {
        foreach($workers as $worker => $data)
        {
            $this->generateSupervisorConfigurationFile($worker, $autostart, $autorestart, $appId, $destination, $output);
        }
    }

    private function generateSupervisorConfigurationFile(string $worker, bool $autostart, bool $autorestart, string $appId, string $destination, OutputInterface $output): void
    {
        $configuration = $this->generateSupervisorConfiguration($worker, $autostart, $autorestart, $appId);
        $filename = $this->buildFilename($appId, $worker);

        $this->filesystem->write($filename, $configuration, $this->overwrite);

        $message = sprintf('%s', $destination . $filename);
        if($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE)
        {
            $message = sprintf("<comment>%s%s</comment>\n%s\n", $destination, $filename, $configuration);
        }

        $output->writeln($message);
    }

    private function buildFilename(string $appId, string $workerName): string
    {
        return sprintf('%s--%s.conf', $appId, $workerName);
    }

    private function generateSupervisorConfiguration(string $worker, bool $autostart, bool $autorestart, string $appId): string
    {
        $program = sprintf('%s--%s', $appId, $worker);
        $command = $this->commandGenerator->generate($worker);
        $autostartAsString = $this->booleanToString($autostart);
        $autorestartAsString = $this->booleanToString($autorestart);

        return <<<TXT
[program:$program]
command=$command
directory=/var/www/app
user=www-data
autostart=$autostartAsString
autorestart=$autorestartAsString
TXT;
    }

    private function booleanToString(bool $value): string
    {
        return $value === true ? 'true' : 'false';
    }
}
