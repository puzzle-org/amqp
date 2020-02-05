<?php

namespace Puzzle\AMQP\Messages\Processors;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Messages\ContentType;
use Symfony\Component\Debug\BufferingLogger;
use Puzzle\Assert\ExampleDataProvider;
use Puzzle\AMQP\Messages\InMemory;
use Puzzle\AMQP\Messages\Bodies\Binary;
use Puzzle\AMQP\MessageMetadata;
use Puzzle\AMQP\Messages\Bodies\Json;

class GZipTest extends TestCase
{
    use ExampleDataProvider;
    
    public function testOnPublish()
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
        
        $this->assertCompressionHeadersArePresent($message, ContentType::TEXT);
        
        $this->assertLessThan(
            strlen($uncompressedContent),
            strlen($message->getBodyInTransportFormat())
        );
    }
    
    private function assertCompressionHeadersArePresent(MessageMetadata $message, $originalContentType)
    {
        $headers = $message->getHeaders();
        $this->assertArrayHasKey(GZip::HEADER_COMPRESSION, $headers);
        $this->assertSame(GZip::COMPRESSION_ALGORITHM, $headers[GZip::HEADER_COMPRESSION]);
        
        $this->assertArrayHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $headers);
        $this->assertSame($originalContentType, $headers[GZip::HEADER_COMPRESSION_CONTENT_TYPE]);
        $this->assertSame(ContentType::BINARY, $message->getContentType());
    }
    
    private function assertCompressionHeadersAreNotPresent(MessageMetadata $message, $originalContentType)
    {
        $headers = $message->getHeaders();
        $this->assertArrayNotHasKey(GZip::HEADER_COMPRESSION, $headers);
        $this->assertArrayNotHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $headers);
        $this->assertSame($originalContentType, $message->getContentType());
    }
    
    public function testOnPublishWithCompressionDisallowed()
    {
        $gzip = new GZip();
        
        $uncompressedContent = str_repeat('123456789', 20000);
        
        $message = new Message('my.key');
        $message->setText($uncompressedContent);
        
        $gzip->onPublish($message);
        
        $this->assertArrayNotHasKey(GZip::HEADER_COMPRESSION, $message->getHeaders());
        
        $this->assertSame(ContentType::TEXT, $message->getContentType());
        
        $this->assertEquals(
            strlen($uncompressedContent),
            strlen($message->getBodyInTransportFormat())
        );
    }
    
    public function testOnPublishWithNullCompressionLevel()
    {
        $gzip = new GZip();
        $gzip->setCompressionLevel(0);
        
        $uncompressedContent = str_repeat('123456789', 20000);
        
        $message = new Message('my.key');
        $message
            ->setText($uncompressedContent)
            ->allowCompression();
        
        $gzip->onPublish($message);
        
        $this->assertCompressionHeadersArePresent($message, ContentType::TEXT);
        
        $this->assertLessThan(100,
            abs(strlen($message->getBodyInTransportFormat()) - strlen($uncompressedContent)),
            "Expected : no compression, just a little size difference due to algorithm headers (less than 100 characters)"
        );
    }
    
    /**
     * @dataProvider providerTestSetCompressionLevelWithInvalidValues
     */
    public function testSetCompressionLevelWithInvalidValues($level)
    {
        $logger = new BufferingLogger();
        
        $gzip = new GZip();
        $gzip->setLogger($logger);
        
        $logger->cleanLogs();
        
        $gzip->setCompressionLevel($level);
        
        $logs = $logger->cleanLogs();
        
        // And this whole test assert than no php fatal errors have been raised (like "Could not be converted to string")
        $this->assertNotEmpty($logs);
    }
    
    public function providerTestSetCompressionLevelWithInvalidValues()
    {
        return [
            ["toto"],
            [array(3)],
            [new \stdClass()],
            [function () { return "toto"; }],
            [$this->buildGenerator()],
        ];
    }
    
    public function testOnConsume()
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

        $this->assertCompressionHeadersAreNotPresent($message, ContentType::TEXT);
        $this->assertSame($originalText, $message->getBodyInOriginalFormat());
    }
    
    public function testOnConsumeWithWrongCompressionAlgorithm()
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
        
        $this->assertArrayHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $message->getHeaders());
        $this->assertSame(ContentType::BINARY, $message->getContentType());
        $this->assertSame($compressedText, $message->getBodyInOriginalFormat());
    }
    
    public function testOnConsumeWithMissingCompressionHeader()
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

        $this->assertArrayHasKey(GZip::HEADER_COMPRESSION_CONTENT_TYPE, $message->getHeaders());
        $this->assertSame(ContentType::BINARY, $message->getContentType());
        $this->assertSame($compressedText, $message->getBodyInOriginalFormat());
    }
    
    public function testOnConsumeWithNoCompressionHeader()
    {
        $content = ['burger' => 'meat'];
        
        $message = InMemory::build(
            'my.key',
            new Json($content)
        );
        
        $gzip = new GZip();
        $message = $gzip->onConsume($message);

        $this->assertCompressionHeadersAreNotPresent($message, ContentType::JSON);
        $this->assertSame($content, $message->getBodyInOriginalFormat());
    }
}
