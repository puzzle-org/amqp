<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\Messages\Bodies\Text;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\Bodies\Binary;

trait BodySetter
{
    public function setText($text)
    {
        $this->setBody(new Text($text));
    }

    public function setJson(array $content)
    {
        $this->setBody(new Json($content));
    }

    public function setBinary($content)
    {
        $this->setBody(new Binary($content));
    }

    abstract public function setBody(Body $body);
}
