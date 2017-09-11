<?php

namespace Puzzle\AMQP\Messages\Chunks;

use PHPUnit\Framework\TestCase;

class ChunkSizeTest extends TestCase
{
    /**
     * @dataProvider providerTestValidChunkSize
     */
    public function testValidChunkSize($expected, $size)
    {
        $chunkSize = new ChunkSize($size);

        $this->assertSame($expected, $chunkSize->toBytes());
    }

    public function providerTestValidChunkSize()
    {
        return [
            [ 3945, 3945 ],
            [ 3945, '3945' ],
            [ 466944, '456K' ],
            [ 10485760, '10M' ],
        ];
    }

    /**
     * @dataProvider providerTestInvalidSize
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidSize($size)
    {
        new ChunkSize($size);
    }

    public function providerTestInvalidSize()
    {
        return [
            [ 'pony' ],
            [ 0 ],
            [ -10 ],
            [ '4G' ],
            [ '0K' ],
            [ '-10M' ],
            [ 5.5 ],
        ];
    }
}
