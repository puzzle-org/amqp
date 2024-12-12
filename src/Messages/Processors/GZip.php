<?php

namespace Puzzle\AMQP\Messages\Processors;

use Puzzle\AMQP\Workers\MessageAdapter;
use Puzzle\AMQP\WritableMessage;
use Puzzle\AMQP\Messages\Bodies\Binary;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\Pieces\StringManipulation;
use Puzzle\AMQP\Messages\OnPublishProcessor;
use Puzzle\AMQP\Messages\OnConsumeProcessor;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Workers\ReadableMessageModifier;
use Puzzle\AMQP\Messages\BodyFactory;
use Puzzle\AMQP\Messages\BodyFactories\Standard;

class GZip implements OnPublishProcessor, OnConsumeProcessor
{
    use
        LoggerAwareTrait,
        StringManipulation;
    
    const string
        HEADER_COMPRESSION = 'compression',
        HEADER_COMPRESSION_CONTENT_TYPE = 'compression_content-type',
        COMPRESSION_ALGORITHM = 'gzip';
    
    private int
        $compressionLevel,
        $encodingMode;
    private BodyFactory
        $bodyFactory;
    
    public function __construct(?BodyFactory $bodyFactory = null)
    {
        $this->compressionLevel = -1;
        $this->encodingMode = FORCE_GZIP;
        $this->logger = new NullLogger();
        
        if(! $bodyFactory instanceof BodyFactory)
        {
            $bodyFactory = new Standard();
        }
        $this->bodyFactory = $bodyFactory;
    }
        
    public function setCompressionLevel($compressionLevel = -1): static
    {
        if(! $this->isCompressionLevelValid($compressionLevel))
        {
            $this->logWarning(sprintf(
                "Invalid compression level (%s)",
                $this->convertToString($compressionLevel)
            ));
            
            return $this;
        }
        
        $this->compressionLevel = (int) $compressionLevel;
        
        return $this;
    }
    
    private function isCompressionLevelValid($level): bool
    {
        return is_numeric($level) && $level >= -1 && $level <= 9;
    }
    
    private function logWarning($message): void
    {
        $this->logger->warning(sprintf(
            "[%s] : %s",
            "PROCESSOR " . __CLASS__,
            $message
        ));
    }
    
    public function setEncodingMode($mode): static
    {
        if(! is_scalar($mode) || ! in_array($mode, [FORCE_GZIP, FORCE_DEFLATE]))
        {
            $this->logWarning(sprintf(
                "Invalid encoding mode (%s)",
                $this->convertToString($mode)
            ));
            
            return $this;
        }
        
        $this->encodingMode = $mode;
        
        return $this;
    }
    
    public function onPublish(WritableMessage $message): void
    {
        if($message->isCompressionAllowed() === false)
        {
            return;
        }
        
        $compressedContent = gzencode($message->getBodyInTransportFormat(), $this->compressionLevel, $this->encodingMode);
        
        $this->updateCompressedMessage($message, $compressedContent);
    }
    
    private function updateCompressedMessage(WritableMessage $message, $compressedContent): void
    {
        $message->addHeaders([
            self::HEADER_COMPRESSION => self::COMPRESSION_ALGORITHM,
            self::HEADER_COMPRESSION_CONTENT_TYPE => $message->getContentType(),
        ]);
        
        $message->setBody(new Binary($compressedContent));
    }
    
    public function onConsume(ReadableMessage $message): ReadableMessage
    {
        if($this->isCompressed($message))
        {
            $message = $this->updateUncompressedMessage(
                $message,
                gzdecode($message->getBodyInOriginalFormat())
            );
        }
        
        return $message;
    }
    
    private function isCompressed(ReadableMessage $message): bool
    {
        $headers = $message->getHeaders();
        
        if(isset($headers[self::HEADER_COMPRESSION])
        && isset($headers[self::HEADER_COMPRESSION_CONTENT_TYPE]))
        {
            return self::COMPRESSION_ALGORITHM === $headers[self::HEADER_COMPRESSION];
        }
        
        return false;
    }
    
    private function updateUncompressedMessage(ReadableMessage $message, mixed $uncompressedContent): ReadableMessage
    {
        $builder = new ReadableMessageModifier($message);
        
        $headers = $message->getHeaders();
        $newContentType = $headers[self::HEADER_COMPRESSION_CONTENT_TYPE];
        
        $newBody = $this->bodyFactory->build($newContentType, $uncompressedContent);
        
        $builder
            ->changeBody($newBody)
            ->changeAttribute('content_type', $newContentType)
            ->dropHeader(self::HEADER_COMPRESSION)
            ->dropHeader(self::HEADER_COMPRESSION_CONTENT_TYPE);
            
        return $builder->build();
    }
}
