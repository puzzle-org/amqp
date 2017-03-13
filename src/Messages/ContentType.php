<?php

namespace Puzzle\AMQP\Messages;

interface ContentType
{
    const
        TEXT = 'text/plain',
        JSON = 'application/json',
        BINARY = 'application/octet-stream',
        EMPTY_CONTENT = 'application/x-empty';
}
