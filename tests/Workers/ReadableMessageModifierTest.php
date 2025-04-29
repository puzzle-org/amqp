<?php

namespace Puzzle\AMQP\Workers;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Puzzle\AMQP\Messages\Bodies\Text;
use Puzzle\AMQP\Messages\BodyFactories\Standard;
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
    
    private function assertBodyIsUnchanged(ReadableMessage $message)
    {
        $this->assertSame(ContentType::EMPTY_CONTENT, $message->getContentType());
        $this->assertEmpty($message->getBodyInOriginalFormat());
    }
    
    private function assertRoutingKeyIsUnchanged(ReadableMessage $message)
    {
        $this->assertSame('my.key', $message->getRoutingKey());
    }
    
    private function assertHeadersAreUnchanged(ReadableMessage $message)
    {
        $headers = $message->getHeaders();
        
        $this->assertArrayHasKey('X-PlusY', $headers);
        $this->assertArrayHasKey('X-Name', $headers);
        $this->assertArrayHasKey('X-Override', $headers);
        
        $this->assertSame(42, $headers['X-PlusY']);
        $this->assertSame('Josselin Vacheron', $headers['X-Name']);
        $this->assertSame('Must change', $headers['X-Override']);
    }
    
    public function testModifyHeaders()
    {
        $builder = new ReadableMessageModifier($this->originalMessage);
        
        $message = $builder
            ->dropHeader('X-Name')
            ->addHeader('X-Override', 'has been changed')
            ->addHeader('X-New', 'new new')
            ->build();
        
        $headers = $message->getHeaders();
        
        $this->assertArrayHasKey('X-PlusY', $headers);
        $this->assertArrayHasKey('X-Override', $headers);
        $this->assertArrayHasKey('X-New', $headers);
        $this->assertArrayNotHasKey('X-Name', $headers);
        
        $this->assertSame('new new', $headers['X-New']);
        $this->assertSame('has been changed', $headers['X-Override']);
        
        $this->assertRoutingKeyIsUnchanged($message);
        $this->assertBodyIsUnchanged($message);
    }
    
    public function testChangeRoutingKey()
    {
        $builder = new ReadableMessageModifier($this->originalMessage);
        
        $message = $builder
            ->changeRoutingKey('plouf')
            ->build();

        $this->assertSame('plouf', $message->getRoutingKey());
        $this->assertSame('my.key', $message->getRoutingKeyFromHeader());
        
        $this->assertBodyIsUnchanged($message);
        $this->assertHeadersAreUnchanged($message);
    }
    
    public function testChangeBody()
    {
        $builder = new ReadableMessageModifier($this->originalMessage);
        
        $content = ['beef' => 'meat'];
        
        $message = $builder
            ->changeBody(new Json($content))
            ->build();

        $this->assertSame($content, $message->getBodyInOriginalFormat());
        $this->assertSame(ContentType::JSON, $message->getContentType());

        $this->assertRoutingKeyIsUnchanged($message);
        $this->assertHeadersAreUnchanged($message);
    }

    public function testBuildWithCustomBodyFactory(): void
    {
        $factory = new Standard();
        $factory->handleContentType('application/pouet', new \Puzzle\AMQP\Messages\TypedBodyFactories\Json());

        $content = '{"a":1}';
        $body = new Text($content);
        $originalMessage = InMemory::build('my.key', $body, [], ['content_type' => 'application/pouet']);
        Assert::assertEquals($content, $originalMessage->getBodyAsTransported());

        $builder = new ReadableMessageModifier($originalMessage);

        $message = $builder->build($factory);

        Assert::assertEquals(['a' => 1], $message->getBodyInOriginalFormat());
        Assert::assertEquals($content, $message->getBodyAsTransported());
    }
}
