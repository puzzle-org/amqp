<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Processors;

use Puzzle\AMQP\Messages\Processor;
use Psr\Log\LoggerAwareTrait;

class NullProcessor implements Processor
{
    use LoggerAwareTrait;
}
