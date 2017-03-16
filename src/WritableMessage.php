<?php

namespace Puzzle\AMQP;

use Puzzle\AMQP\Messages\Body;

interface WritableMessage extends MessageMetadata
{
    /**
     * @return int
     */
    public function getFlags();
    
    /**
     * @return void
     */
    public function setFlags($flags);
    
    /**
     * @return mixed
     */
    public function getBodyInTransportFormat();
    
    /**
     * @return void
     */
    public function setBody(Body $body);

    /**
     * @return void
     */
    public function setExpiration($expirationInSeconds);

    /**
     * @return void
     */
    public function addHeader($headerName, $value);

    /**
     * @return void
     */
    public function addHeaders(array $headers);

    /**
     * @return void
     */
    public function packAttributes($timestamp = false);
    
    /**
     * @return void
     */
    public function setAttribute($attributeName, $value);
}
