<?php

namespace Puzzle\AMQP\Services;

use Puzzle\Pieces\PathManipulation;

class CommandGenerator
{
    const WORKER_ENTRYPOINT_FILENAME = 'worker';

    use
        PathManipulation;

    public function generate($worker)
    {
        $pattern = 'php worker run %s';

        return sprintf($pattern, $worker);
    }
}
