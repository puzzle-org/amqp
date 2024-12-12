<?php

namespace Puzzle\AMQP\Messages\Chunks;

final class ChunkSize
{
    private int
        $size;
    private ?string
        $unit;

    public function __construct($size)
    {
        $exception = new \InvalidArgumentException("Given chunk size is not valid");

        if(preg_match("~^(\d+)(K|M)?$~", $size, $matches) !== 1)
        {
            throw $exception;
        }

        $this->size = (int) $matches[1];
        if($this->size <= 0)
        {
            throw $exception;
        }

        $this->unit = null;
        if(isset($matches[2]))
        {
            $this->unit = $matches[2];
        }
    }

    public function toBytes(): int
    {
        $unitsConversion = [
            "K" => 1024,
            "M" => 1024 ** 2,
        ];

        if(empty($this->unit))
        {
            return $this->size;
        }

        return $this->size * $unitsConversion[$this->unit];
    }
}
