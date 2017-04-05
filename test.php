<?php

require 'vendor/autoload.php';

use Puzzle\Configuration\Yaml;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Messages\Message;
use Puzzle\AMQP\Client;
use Puzzle\AMQP\Messages\Processors\GZip;
use Ramsey\Uuid\Uuid;

$configuration = new Yaml(new Filesystem(new Local(__DIR__ . '/config')));
$client = new Pecl($configuration);
$client->appendMessageProcessor(new GZip());

class ChunkedDocument
{
    public
        $uuid,
        $size,
        $checksum,
        $nbChunks;
    
    public function __construct($size, $checksum, $chunkSize)
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->size = $size;
        $this->checksum = $checksum;
        $this->nbChunks = (int) ceil($size / $chunkSize);
    }
}

class Chunk
{
    public
        $offset,
        $playhead,
        $size,
        $document,
        $content;
    
    public function __construct($offset, $playhead, $content, ChunkedDocument $document)
    {
        $this->offset = $offset;
        $this->playhead = $playhead;
        $this->content = $content;
        $this->size = strlen($content);
        $this->document = $document;
    }
}

class MessageSplitter
{
    private
        $chunkProvider;
    
    public function __construct(Iterator $chunkProvider)
    {
        $this->chunkProvider = $chunkProvider;
    }
    
    public function publish(Client $client, $exchangeName)
    {
        foreach($this->chunkProvider as $chunk)
        {
            $message = new Message('normal.part');
            $message->setBinary($chunk->content);
 //           $message->allowCompression();
            
            $message->addHeaders([
                'chunkedDocument' => [
                    'uuid' => $chunk->document->uuid,
                    'size' => $chunk->document->size,
                    'checksum' => $chunk->document->checksum,
                    'nbChunks' => $chunk->document->nbChunks,
                ],
                'chunk' => [
                    'offset' => $chunk->offset,
                    'playhead' => $chunk->playhead,
                    'size' => $chunk->size,
                ]
            ]);
            
            $client->publish($exchangeName, $message);
            
            unset($message);
            unset($chunk);
        }
    }
}

$chunkSize= 1000000;

$chunkProvider= function() use($chunkSize) {
    $offset = 0;
    $playhead = 0;
    $filepath = 'lutece-2013-04-06.tgz'; // 'composer.phar';
    $stream = fopen($filepath, 'r');
    $document = new ChunkedDocument(filesize($filepath), sha1_file($filepath), $chunkSize);
    
    while (! feof($stream))
    {
        $content = fread($stream, $chunkSize);
        $contentSize = strlen($content);
        $playhead++;
        
        if($playhead % 71 === 0)
        {
            //gc_collect_cycles();
            echo "YOLO\n";
            meminfo_objects_list(fopen('var/obj.json', 'w'));
            meminfo_info_dump(fopen('var/poc.json', 'w'));
            meminfo_objects_summary(fopen('var/summary.json', 'w'));
        }
        
        $chunk = new Chunk($offset, $playhead, $content, $document);
        yield $chunk;
        
        unset($chunk, $content);
        $offset += $contentSize;
    }
    
    fclose($stream);
};

$splitter = new MessageSplitter($chunkProvider());
$splitter->publish($client, 'puzzle');
