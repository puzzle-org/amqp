<?php

namespace Puzzle\AMQP\Messages;

interface BodyFactory
{
    /**
     * @return Body
     */
    public function build($contentType, $contentAsTransported);
}
