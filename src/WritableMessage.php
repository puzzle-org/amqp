<?php

namespace Puzzle\AMQP;

use Puzzle\AMQP\Messages\Body;

interface WritableMessage extends MessageMetadata
{
    public function getFormattedBody();
    public function setBody(Body $body);

    public function setFlags($flags);
    public function setExpiration($expirationInSeconds);

    public function addHeader($headerName, $value);
    public function addHeaders(array $headers);

    public function packAttributes($timestamp = false);
    public function setAttribute($attributeName, $value);
}
