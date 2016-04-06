<?php

namespace Puzzle\AMQP;

interface WritableMessage extends Message
{
    public function getFormattedBody();
    public function setBody($body);

    public function setFlags($flags);
    public function setExpiration($expirationInSeconds);

    public function addHeader($headerName, $value);
    public function addHeaders(array $headers);

    public function packAttributes($timestamp = false);
    public function setAttribute($attributeName, $value);
}
