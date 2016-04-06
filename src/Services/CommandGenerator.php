<?php

namespace Puzzle\AMQP\Services;

use Puzzle\Pieces\PathManipulation;

class CommandGenerator
{
    const WORKER_ENTRYPOINT_FILENAME = 'worker';

    use
        PathManipulation;

    private
        $rootPath;

    public function __construct($rootPath)
    {
        $this->rootPath = $this->enforceEndingSlash($rootPath);
    }

    public function generate($worker)
    {
        $pattern = '/usr/bin/php worker run %s';

        return sprintf($pattern, $worker);
    }
}
