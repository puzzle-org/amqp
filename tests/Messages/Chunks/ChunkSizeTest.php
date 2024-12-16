<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Chunks;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChunkSizeTest extends TestCase
{
    #[DataProvider('providerTestValidChunkSize')]
    public function testValidChunkSize($expected, $size): void
    {
        $chunkSize = new ChunkSize($size);

        self::assertSame($expected, $chunkSize->toBytes());
    }

    public static function providerTestValidChunkSize(): array
    {
        return [
            [ 3945, 3945 ],
            [ 3945, '3945' ],
            [ 466944, '456K' ],
            [ 10485760, '10M' ],
        ];
    }

    #[DataProvider('providerTestInvalidSize')]
    public function testInvalidSize($size): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ChunkSize($size);
    }

    public static function providerTestInvalidSize(): array
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
