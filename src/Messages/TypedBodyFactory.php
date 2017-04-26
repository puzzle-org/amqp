<?php

namespace Puzzle\AMQP\Messages;

interface TypedBodyFactory
{
    /**
     * @return \Puzzle\AMQP\Messages\Body
     */
    public function build($contentAsTransported);
}
