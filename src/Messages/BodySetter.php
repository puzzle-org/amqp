<?php

namespace Puzzle\AMQP\Messages;

use Puzzle\AMQP\Messages\Bodies\Text;
use Puzzle\AMQP\Messages\Bodies\Json;
use Puzzle\AMQP\Messages\Bodies\Binary;
use Puzzle\AMQP\Messages\Bodies\StreamedFile;
use Puzzle\AMQP\Messages\Chunks\ChunkSize;

trait BodySetter
{
    public function setText($text)
    {
        $this->setBody(new Text($text));

        return $this;
    }

    public function setJson(array $content)
    {
        $this->setBody(new Json($content));

        return $this;
    }

    public function setBinary($content)
    {
        $this->setBody(new Binary($content));

        return $this;
    }

    public function setStreamedFile($filepath, ChunkSize $size)
    {
        $this->setBody(new StreamedFile($filepath, $size));

        $this->addHeaders([
            'file' => [
                'path' => dirname($filepath),
                'filename' => basename($filepath),
            ]
        ]);

        return $this;
    }

    abstract public function setBody(Body $body);
}
