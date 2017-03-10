<?php

namespace Puzzle\AMQP\Collections;

use Puzzle\AMQP\MessageHook;

class MessageHookCollection implements \IteratorAggregate
{
    private
        $hooks;

    public function __construct(array $hooks = array())
    {
        $this->hooks = array_filter($hooks, function($hook) {
            return ($hook instanceof MessageHook);
        });
    }

    public function add(MessageHook $hook)
    {
        $this->hooks[] = $hook;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->hooks);
    }
}
