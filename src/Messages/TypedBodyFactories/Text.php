<?php

namespace Puzzle\AMQP\Messages\TypedBodyFactories;

use Puzzle\AMQP\Messages\Bodies;
use Puzzle\AMQP\Messages\TypedBodyFactory;

class Text implements TypedBodyFactory
{
    public function build($contentAsTransported)
    {
        return new Bodies\Text($contentAsTransported);
    }
}
