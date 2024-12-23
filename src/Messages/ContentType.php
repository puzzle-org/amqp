<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages;

interface ContentType
{
    const string
        TEXT = 'text/plain',
        JSON = 'application/json',
        BINARY = 'application/octet-stream',
        EMPTY_CONTENT = 'application/x-empty';
}
