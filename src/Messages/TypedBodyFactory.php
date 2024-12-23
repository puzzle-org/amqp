<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages;

interface TypedBodyFactory
{
    public function build($contentAsTransported): Body;
}
