<?php

namespace Puzzle\AMQP\Services;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Symfony\Component\Console\Output\NullOutput;

class SupervisorConfigurationGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestGenerate
     */
    public function testGenerate($server, $workers, $autostart, $autorestart, $expectedFilenames, $expectedTemplates)
    {
        $workers = $this->generateWorkers($workers);

        $filesystem = new Filesystem(new InMemory([]));

        $output = new NullOutput();

        $generator = new SupervisorConfigurationGenerator($filesystem, new CommandGenerator(__DIR__));

        $generator->generate($workers, $autostart, $autorestart, $server, 'burger_poney', 'var/system/supervisor', $output);

        foreach($filesystem->keys() as $filename)
        {
            $this->assertTrue(in_array($filename, $expectedFilenames), sprintf('The file %s cannot be found.', $filename));

            $key = array_search($filename, $expectedFilenames);
            $this->assertEquals($expectedTemplates[$key], $filesystem->get($filename)->getContent());
        }
    }

    public function providerTestGenerate()
    {
        $appId = 'burger_poney';

        return [

            'one worker, one server'
                => array(
                    'server' => 'worker',
                    'workers' => [
                        'poney.run-1-worker-1',
                    ],
                    'autostart' => true,
                    'autorestart' => true,
                    'files' => [
                        'burger_poney--poney.run.conf',
                    ],
                    'templates' => [
                        $this->generateExpectedTemplate($appId, 'poney.run', true, true),
                    ]
            ),

            'many worker, one server'
                => array(
                    'server' => 'worker',
                    'workers' => [
                        'poney.run-1-worker-1',
                        'poney.eat-1-worker-1',
                        'poney.sleep-1-worker-1'
                    ],
                    'autostart' => true,
                    'autorestart' => true,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                        'burger_poney--poney.sleep.conf'
                    ],
                    'templates' => [
                        $this->generateExpectedTemplate($appId, 'poney.run', true, true),
                        $this->generateExpectedTemplate($appId, 'poney.eat', true, true),
                        $this->generateExpectedTemplate($appId, 'poney.sleep', true, true)
                    ],
                ),

            'autostart disabled, autorestart enabled'
                => array(
                    'server' => 'worker',
                    'workers' => [
                        'poney.run-1-worker-1',
                        'poney.eat-1-worker-1',
                        'poney.sleep-1-worker-1'
                    ],
                    'autostart' => false,
                    'autorestart' => true,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                        'burger_poney--poney.sleep.conf'
                    ],
                    'templates' => [
                        $this->generateExpectedTemplate($appId, 'poney.run', false, true),
                        $this->generateExpectedTemplate($appId, 'poney.eat', false, true),
                        $this->generateExpectedTemplate($appId, 'poney.sleep', false, true)
                    ],
                ),

            'autostart disabled, autorestart disabled'
                => array(
                    'server' => 'worker',
                    'workers' => [
                        'poney.run-1-worker-1',
                        'poney.eat-1-worker-1',
                        'poney.sleep-1-worker-1'
                    ],
                    'autostart' => false,
                    'autorestart' => false,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                        'burger_poney--poney.sleep.conf'
                    ],
                    'templates' => [
                        $this->generateExpectedTemplate($appId, 'poney.run', false, false),
                        $this->generateExpectedTemplate($appId, 'poney.eat', false, false),
                        $this->generateExpectedTemplate($appId, 'poney.sleep', false, false)
                    ],
                ),

            'autostart enabled, autorestart disabled'
                => array(
                    'server' => 'worker',
                    'workers' => [
                        'poney.run-1-worker-1',
                        'poney.eat-1-worker-1',
                        'poney.sleep-1-worker-1'
                    ],
                    'autostart' => true,
                    'autorestart' => false,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                        'burger_poney--poney.sleep.conf'
                    ],
                    'templates' => [
                        $this->generateExpectedTemplate($appId, 'poney.run', true, false),
                        $this->generateExpectedTemplate($appId, 'poney.eat', true, false),
                        $this->generateExpectedTemplate($appId, 'poney.sleep', true, false)
                    ],
                ),

            'one server not corresponding to any worker'
                => array(
                    'server' => 'worker',
                    'workers' => [
                        'poney.run-1-front-1',
                        'poney.eat-1-front-1',
                        'poney.sleep-1-front-1'
                    ],
                    'autostart' => true,
                    'autorestart' => true,
                    'files' => [
                    ],
                    'templates' => [
                    ],
                ),

            'one server corresponding to few workers'
            => array(
                'server' => 'worker',
                'workers' => [
                    'poney.run-1-front-1',
                    'poney.eat-1-worker-1',
                    'poney.sleep-1-front-1'
                ],
                'autostart' => true,
                'autorestart' => true,
                'files' => [
                    'burger_poney--poney.eat.conf',
                ],
                'templates' => [
                    $this->generateExpectedTemplate($appId, 'poney.eat', true, true),
                ],
            ),

            'one worker disabled'
                => array(
                    'server' => 'worker',
                    'workers' => [
                        'poney.run-1-worker-1',
                        'poney.eat-1-worker-1',
                        'poney.sleep-1-worker-0'
                    ],
                    'autostart' => true,
                    'autorestart' => true,
                    'files' => [
                        'burger_poney--poney.run.conf',
                        'burger_poney--poney.eat.conf',
                    ],
                    'templates' => [
                        $this->generateExpectedTemplate($appId, 'poney.run', true, true),
                        $this->generateExpectedTemplate($appId, 'poney.eat', true, true),
                    ],
                ),

            'all workers disabled'
                => array(
                    'server' => 'worker',
                    'workers' => [
                        'poney.run-1-worker-0',
                        'poney.eat-1-worker-0',
                        'poney.sleep-1-worker-0'
                    ],
                    'autostart' => true,
                    'autorestart' => true,
                    'files' => [
                    ],
                    'templates' => [
                    ],
                ),

        ];
    }

    private function generateExpectedTemplate($appId, $worker, $autostart, $autorestart)
    {
        $autostart = $autostart === true ? 'true' : 'false';
        $autorestart = $autorestart === true ? 'true' : 'false';

        return <<<TXT
[program:$appId--$worker]
command=/usr/bin/php worker run $worker
directory=/var/www/app
user=www-data
autostart=$autostart
autorestart=$autorestart
TXT;
    }

    private function generateWorkers(array $workers)
    {
        $generatedWorkers = array();
        foreach($workers as $worker)
        {
            list($name, $instances, $servers, $isDeploymentAllowed) = explode('-', $worker);

            $generatedWorkers[$name] = [
                'instances' => intval($instances),
                'servers' => explode('.', $servers),
                'isDeploymentAllowed' => (bool) $isDeploymentAllowed
            ];
        }

        return $generatedWorkers;
    }
}
