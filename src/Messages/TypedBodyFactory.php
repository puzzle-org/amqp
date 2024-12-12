<?php

namespace Puzzle\AMQP\Messages;

interface TypedBodyFactory
{
    public function build($contentAsTransported): Body;
}
