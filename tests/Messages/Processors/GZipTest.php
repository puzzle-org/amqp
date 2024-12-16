<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Messages\Processors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\ContentType;
use Symfony\Component\Debug\BufferingLogger;
use Puzzle\AMQP\Messages\InMemory;
use Puzzle\AMQP\Messages\Bodies\Binary;
use Puzzle\AMQP\MessageMetadata;
use Puzzle\AMQP\Messages\Bodies\Json;

class GZipTest extends TestCase
{
    public function testOnPublish(): void
    {
        $gzip = new GZip();
        $gzip->setEncodingMode(new \stdClass()); // do not trigger fatal error
        $gzip->setEncodingMode(FORCE_GZIP);
        
        $uncompressedContent = str_repeat('123456789', 20000);
        
        $message = new Message('my.key');
        $message
            ->setText($uncompressedContent)
            ->allowCompression();
        
        $gzip->onPublish($message);
        
        self::assertCompressionHeadersArePresent($message, ContentType::TEXT);
        
        $this->assertLessThan(
            strlen($uncompressedContent),
            strlen($message->getBodyInTransportFormat())
        );
    }
    
    private static function assertCompressionHeadersArePresent(MessageMetadata $message, $originalContentType): void
    {
        $headers = $message->getHeaders();
        self::assertArrayHasKey(GZip::HEADER_COMPRESSION, $headers);
        self::assertSame(GZip::COMPRESSION_ALGORITHM, $headers[GZip::HEADER_COMPRESSION]);
        
        self::assertArrayHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $headers);
        self::assertSame($originalContentType, $headers[GZip::HEADER_COMPRESSION_CONTENT_TYPE]);
        self::assertSame(ContentType::BINARY, $message->getContentType());
    }
    
    private static function assertCompressionHeadersAreNotPresent(MessageMetadata $message, $originalContentType): void
    {
        $headers = $message->getHeaders();
        self::assertArrayNotHasKey(GZip::HEADER_COMPRESSION, $headers);
        self::assertArrayNotHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $headers);
        self::assertSame($originalContentType, $message->getContentType());
    }
    
    public function testOnPublishWithCompressionDisallowed(): void
    {
        $gzip = new GZip();
        
        $uncompressedContent = str_repeat('123456789', 20000);
        
        $message = new Message('my.key');
        $message->setText($uncompressedContent);
        
        $gzip->onPublish($message);
        
        self::assertArrayNotHasKey(GZip::HEADER_COMPRESSION, $message->getHeaders());
        
        self::assertSame(ContentType::TEXT, $message->getContentType());
        
        self::assertEquals(
            strlen($uncompressedContent),
            strlen($message->getBodyInTransportFormat())
        );
    }
    
    public function testOnPublishWithNullCompressionLevel(): void
    {
        $gzip = new GZip();
        $gzip->setCompressionLevel(0);
        
        $uncompressedContent = str_repeat('123456789', 20000);
        
        $message = new Message('my.key');
        $message
            ->setText($uncompressedContent)
            ->allowCompression();
        
        $gzip->onPublish($message);
        
        self::assertCompressionHeadersArePresent($message, ContentType::TEXT);
        
        self::assertLessThan(100,
            abs(strlen($message->getBodyInTransportFormat()) - strlen($uncompressedContent)),
            "Expected : no compression, just a little size difference due to algorithm headers (less than 100 characters)"
        );
    }
    
    #[DataProvider('providerTestSetCompressionLevelWithInvalidValues')]
    public function testSetCompressionLevelWithInvalidValues($level): void
    {
        $logger = new BufferingLogger();
        
        $gzip = new GZip();
        $gzip->setLogger($logger);
        
        $logger->cleanLogs();
        
        $gzip->setCompressionLevel($level);
        
        $logs = $logger->cleanLogs();
        
        // And this whole test assert than no php fatal errors have been raised (like "Could not be converted to string")
        self::assertNotEmpty($logs);
    }

    public static function providerTestSetCompressionLevelWithInvalidValues(): array
    {
        return [
            ["toto"],
            [array(3)],
            [new \stdClass()],
            [function () { return "toto"; }],
            [self::buildGenerator()],
        ];
    }

    private static function buildGenerator(): \Generator
    {
        $closure = static function () { yield 3; };

        return $closure();
    }
    
    public function testOnConsume(): void
    {
        $originalText = "Burger over unicorns through a beautiful rainbow !";
        
        $message = InMemory::build(
            'my.key',
            new Binary(gzencode($originalText)),
            [
                Gzip::HEADER_COMPRESSION => Gzip::COMPRESSION_ALGORITHM,
                GZip::HEADER_COMPRESSION_CONTENT_TYPE => ContentType::TEXT
            ]
        );
        
        $gzip = new GZip();
        $message = $gzip->onConsume($message);

        self::assertCompressionHeadersAreNotPresent($message, ContentType::TEXT);
        self::assertSame($originalText, $message->getBodyInOriginalFormat());
    }
    
    public function testOnConsumeWithWrongCompressionAlgorithm(): void
    {
        $originalText = "Burger over unicorns through a beautiful rainbow !";
        
        $message = InMemory::build(
            'my.key',
            new Binary($compressedText = gzencode($originalText)),
            [
                Gzip::HEADER_COMPRESSION => "Rar",
                GZip::HEADER_COMPRESSION_CONTENT_TYPE => ContentType::TEXT
            ]
        );
        
        $gzip = new GZip();
        $message = $gzip->onConsume($message);
        
        self::assertArrayHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $message->getHeaders());
        self::assertSame(ContentType::BINARY, $message->getContentType());
        self::assertSame($compressedText, $message->getBodyInOriginalFormat());
    }
    
    public function testOnConsumeWithMissingCompressionHeader(): void
    {
        $originalText = "Burger over unicorns through a beautiful rainbow !";
        
        $message = InMemory::build(
            'my.key',
            new Binary($compressedText = gzencode($originalText)),
            [
                GZip::HEADER_COMPRESSION_CONTENT_TYPE => ContentType::TEXT
            ]
        );
        
        $gzip = new GZip();
        $message = $gzip->onConsume($message);

        self::assertArrayHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $message->getHeaders());
        self::assertSame(ContentType::BINARY, $message->getContentType());
        self::assertSame($compressedText, $message->getBodyInOriginalFormat());
    }
    
    public function testOnConsumeWithNoCompressionHeader(): void
    {
        $content = ['burger' => 'meat'];
        
        $message = InMemory::build(
            'my.key',
            new Json($content)
        );
        
        $gzip = new GZip();
        $message = $gzip->onConsume($message);

        self::assertCompressionHeadersAreNotPresent($message, ContentType::JSON);
        self::assertSame($content, $message->getBodyInOriginalFormat());
    }
}
