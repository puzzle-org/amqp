<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages;

interface BodyFactory
{
    public function build($contentType, $contentAsTransported): Body;
}
