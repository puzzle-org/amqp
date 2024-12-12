<?php

namespace Puzzle\AMQP\Messages;

interface BodyFactory
{
    public function build($contentType, $contentAsTransported): Body;
}
