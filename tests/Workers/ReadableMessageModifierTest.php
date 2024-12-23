<?php

declare(strict_types = 1);

namespace Puzzle\AMQP\Workers;

use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\InMemory;
use Puzzle\AMQP\Messages\ContentType;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Messages\Bodies\Json;

class ReadableMessageModifierTest extends TestCase
{
    private
        $originalMessage;
    
    protected function setUp(): void
    {
        $this->originalMessage= InMemory::build('my.key', null, [
            'X-PlusY' => 42,
            'X-Name' => 'Josselin Vacheron',
            'X-Override' => 'Must change'
        ]);
    }
    
    private function assertBodyIsUnchanged(ReadableMessage $message): void
    {
        self::assertSame(ContentType::EMPTY_CONTENT, $message->getContentType());
        self::assertEmpty($message->getBodyInOriginalFormat());
    }
    
    private function assertRoutingKeyIsUnchanged(ReadableMessage $message): void
    {
        self::assertSame('my.key', $message->getRoutingKey());
    }
    
    private function assertHeadersAreUnchanged(ReadableMessage $message): void
    {
        $headers = $message->getHeaders();

        self::assertArrayHasKey('X-Name', $headers);
        self::assertArrayHasKey('X-Override', $headers);
        self::assertArrayHasKey('X-PlusY', $headers);

        self::assertSame(42, $headers['X-PlusY']);
        self::assertSame('Josselin Vacheron', $headers['X-Name']);
        self::assertSame('Must change', $headers['X-Override']);
    }
    
    public function testModifyHeaders(): void
    {
        $builder = new ReadableMessageModifier($this->originalMessage);
        
        $message = $builder
            ->dropHeader('X-Name')
            ->addHeader('X-Override', 'has been changed')
            ->addHeader('X-New', 'new new')
            ->build();
        
        $headers = $message->getHeaders();
        
        self::assertArrayHasKey('X-PlusY', $headers);
        self::assertArrayHasKey('X-Override', $headers);
        self::assertArrayHasKey('X-New', $headers);
        self::assertArrayNotHasKey('X-Name', $headers);
        
        self::assertSame('new new', $headers['X-New']);
        self::assertSame('has been changed', $headers['X-Override']);
        
        $this->assertRoutingKeyIsUnchanged($message);
        $this->assertBodyIsUnchanged($message);
    }
    
    public function testChangeRoutingKey(): void
    {
        $builder = new ReadableMessageModifier($this->originalMessage);
        
        $message = $builder
            ->changeRoutingKey('plouf')
            ->build();

        self::assertSame('plouf', $message->getRoutingKey());
        self::assertSame('my.key', $message->getRoutingKeyFromHeader());
        
        $this->assertBodyIsUnchanged($message);
        $this->assertHeadersAreUnchanged($message);
    }
    
    public function testChangeBody(): void
    {
        $builder = new ReadableMessageModifier($this->originalMessage);
        
        $content = ['beef' => 'meat'];
        
        $message = $builder
            ->changeBody(new Json($content))
            ->build();

        self::assertSame($content, $message->getBodyInOriginalFormat());
        self::assertSame(ContentType::JSON, $message->getContentType());

        $this->assertRoutingKeyIsUnchanged($message);
        $this->assertHeadersAreUnchanged($message);
    }
}
