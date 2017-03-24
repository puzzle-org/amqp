<?php

namespace Puzzle\AMQP;

use Puzzle\AMQP\Messages\Body;

interface WritableMessage extends MessageMetadata
{
    /**
     * @return int
     */
    public function canBeDroppedSilently();
    
    /**
     * @return self
     */
    public function disallowSilentDropping();
    
    /**
     * @return mixed
     */
    public function getBodyInTransportFormat();
    
    /**
     * @return self
     */
    public function setBody(Body $body);

    /**
     * @return void
     */
    public function setExpiration($expirationInSeconds);

    /**
     * @return self
     */
    public function addHeader($headerName, $value);

    /**
     * @return self
     */
    public function addHeaders(array $headers);
    
    /**
     * @return self
     */
    public function setAuthor($author);

    /**
     * @return void
     */
    public function packAttributes($timestamp = false);
    
    /**
     * @return self
     */
    public function setAttribute($attributeName, $value);

    /**
     * @return void
     */
    public function changeRoutingKey($routingKey);
}
