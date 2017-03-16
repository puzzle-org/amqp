<?php

namespace Puzzle\AMQP\Messages;

interface Footprintable
{
    /**
     * @return string
     */
    public function footprint();
}
