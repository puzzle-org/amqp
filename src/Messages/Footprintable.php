<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages;

interface Footprintable
{
    public function footprint(): string;
}
