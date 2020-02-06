<?php

namespace Puzzle\AMQP\Messages;

interface Footprintable
{
    public function footprint(): string;
}
