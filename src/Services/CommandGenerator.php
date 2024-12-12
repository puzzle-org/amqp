<?php

namespace Puzzle\AMQP\Services;

use Puzzle\Pieces\PathManipulation;

class CommandGenerator
{
    const string WORKER_ENTRYPOINT_FILENAME = 'worker';

    use PathManipulation;

    public function generate(string $worker): string
    {
        $pattern = '/usr/bin/env php worker run %s';

        return sprintf($pattern, $worker);
    }
}
