<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Services;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

class SupervisorConfigurationGeneratorTest extends TestCase
{
    #[DataProvider('providerTestGenerate')]
    public function testGenerate($workers, $autostart, $autorestart, $files, $templates): void
    {
        $workers = $this->generateWorkers($workers);

        $filesystem = new Filesystem(new InMemory([]));

        $output = new NullOutput();

        $generator = new SupervisorConfigurationGenerator($filesystem, new CommandGenerator(__DIR__));

        $generator->generate($workers, $autostart, $autorestart, 'burger_poney', 'var/system/supervisor', $output);

        foreach($filesystem->keys() as $filename)
        {
            self::assertTrue(in_array($filename, $files), sprintf('The file %s cannot be found in %s', $filename, implode(',', $files)));

            $key = array_search($filename, $files);
            self::assertEquals($templates[$key], $filesystem->get($filename)->getContent());
        }
    }

    public static function providerTestGenerate(): array
    {
        $appId = 'burger_poney';

        return [

            'one worker, one server'
                => array(
                    'workers' => [
                        'poney.run',
                    ],
                    'autostart' => true,
                    'autorestart' => true,
                    'files' => [
                        'burger_poney--poney.run.conf',
                    ],
                    'templates' => [
                        self::generateExpectedTemplate($appId, 'poney.run', true, true),
                    ]
            ),

            'many worker, one server'
                => array(
                    'workers' => [
                        'poney.run',
                        'poney.eat',
                        'poney.sleep'
                    ],
                    'autostart' => true,
                    'autorestart' => true,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                        'burger_poney--poney.sleep.conf'
                    ],
                    'templates' => [
                        self::generateExpectedTemplate($appId, 'poney.run', true, true),
                        self::generateExpectedTemplate($appId, 'poney.eat', true, true),
                        self::generateExpectedTemplate($appId, 'poney.sleep', true, true)
                    ],
                ),

            'autostart disabled, autorestart enabled'
                => array(
                    'workers' => [
                        'poney.run',
                        'poney.eat',
                        'poney.sleep'
                    ],
                    'autostart' => false,
                    'autorestart' => true,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                        'burger_poney--poney.sleep.conf'
                    ],
                    'templates' => [
                        self::generateExpectedTemplate($appId, 'poney.run', false, true),
                        self::generateExpectedTemplate($appId, 'poney.eat', false, true),
                        self::generateExpectedTemplate($appId, 'poney.sleep', false, true)
                    ],
                ),

            'autostart disabled, autorestart disabled'
                => array(
                    'workers' => [
                        'poney.run',
                        'poney.eat',
                        'poney.sleep'
                    ],
                    'autostart' => false,
                    'autorestart' => false,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                        'burger_poney--poney.sleep.conf'
                    ],
                    'templates' => [
                        self::generateExpectedTemplate($appId, 'poney.run', false, false),
                        self::generateExpectedTemplate($appId, 'poney.eat', false, false),
                        self::generateExpectedTemplate($appId, 'poney.sleep', false, false)
                    ],
                ),

            'autostart enabled, autorestart disabled'
                => array(
                    'workers' => [
                        'poney.run',
                        'poney.eat',
                        'poney.sleep'
                    ],
                    'autostart' => true,
                    'autorestart' => false,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                        'burger_poney--poney.sleep.conf'
                    ],
                    'templates' => [
                        self::generateExpectedTemplate($appId, 'poney.run', true, false),
                        self::generateExpectedTemplate($appId, 'poney.eat', true, false),
                        self::generateExpectedTemplate($appId, 'poney.sleep', true, false)
                    ],
                ),

        ];
    }

    private static function generateExpectedTemplate($appId, $worker, $autostart, $autorestart): string
    {
        $autostart = $autostart === true ? 'true' : 'false';
        $autorestart = $autorestart === true ? 'true' : 'false';

        return <<<TXT
            [program:$appId--$worker]
            command=/usr/bin/env php worker run $worker
            directory=/var/www/app
            user=www-data
            autostart=$autostart
            autorestart=$autorestart
            TXT;
    }

    private function generateWorkers(array $workers): array
    {
        return array_flip($workers);
    }
}
