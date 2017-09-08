<?php

namespace Puzzle\AMQP\Services;

use Gaufrette\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

class SupervisorConfigurationGenerator
{
    private
        $overwrite,
        $filesystem,
        $commandGenerator;

    public function __construct(Filesystem $filesystem, CommandGenerator $commandGenerator)
    {
        $this->filesystem = $filesystem;
        $this->commandGenerator = $commandGenerator;
        $this->overwrite = true;
    }

    public function generate(array $workers, $autostart, $autorestart, $appId, $destination, OutputInterface $output)
    {
        foreach($workers as $worker => $data)
        {
            $this->generateSupervisorConfigurationFile($worker, $autostart, $autorestart, $appId, $destination, $output);
        }
    }

    private function generateSupervisorConfigurationFile($worker, $autostart, $autorestart, $appId, $destination, OutputInterface $output)
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

    private function buildFilename($appId, $workerName)
    {
        return sprintf('%s--%s.conf', $appId, $workerName);
    }

    private function generateSupervisorConfiguration($worker, $autostart, $autorestart, $appId)
    {
        $program = sprintf('%s--%s', $appId, $worker);
        $command = $this->commandGenerator->generate($worker);
        $autostart = $this->booleanToString($autostart);
        $autorestart = $this->booleanToString($autorestart);

        return <<<TXT
[program:$program]
command=$command
directory=/var/www/app
user=www-data
autostart=$autostart
autorestart=$autorestart
TXT;
    }

    private function booleanToString($value)
    {
        return $value === true ? 'true' : 'false';
    }
}
