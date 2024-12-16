<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\TypedBodyFactories;

use Puzzle\AMQP\Messages\Bodies;
use Puzzle\AMQP\Messages\TypedBodyFactory;

class Json implements TypedBodyFactory
{
    public function build($contentAsTransported): Bodies\Json
    {
        $body = new Bodies\Json();
        $body->changeContentWithJson($contentAsTransported);
        
        return $body;
    }
}
