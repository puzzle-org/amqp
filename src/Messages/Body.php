<?php

namespace Puzzle\AMQP\Messages;

interface Body
{
    /**
     * @return string formatted body
     */
    public function format();
    
    /**
     * @return string
     */
    public function getContentType();
    
    /**
     * @return string
     */
    public function __toString();
    
    /**
     * @return mixed
     */
    public function decode();
}
