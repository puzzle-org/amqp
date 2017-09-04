<?php

namespace Puzzle\AMQP\Workers\Chunks\ChunkAssemblyStorages;

use Puzzle\AMQP\Workers\Chunks\ChunkAssemblyStorage;
use Puzzle\ValueObjects\Uuid;
use Puzzle\AMQP\Messages\Chunks\ChunkMetadata;
use Puzzle\AMQP\Messages\Chunks\ChunkedMessageMetadata;

class FileStorage implements ChunkAssemblyStorage
{
    private
        $tempDirPath;

    public function __construct($varPath)
    {
        $this->tempDirPath = $varPath . 'tmp' . DIRECTORY_SEPARATOR;
    }

    public function start(ChunkedMessageMetadata $metadata)
    {
        $this->ensureTempDirectoryExists();
        $this->createFile($metadata);
    }

    private function ensureTempDirectoryExists()
    {
        if(! is_dir($this->tempDirPath))
        {
            mkdir($this->tempDirPath, 0755, true);
        }
    }

    private function computeFilepath(Uuid $uuid)
    {
        return $this->tempDirPath . sprintf("%s.tmp", $uuid);
    }

    private function createFile(ChunkedMessageMetadata $metadata)
    {
        $filepath = $this->computeFilepath($metadata->uuid());

        if(is_file($filepath))
        {
            throw new \LogicException("File $filepath already exists");
        }

        $success = touch($filepath);

        if($success === false)
        {
            throw new \RuntimeException("Cannot create temporary file ($filepath)");
        }

        // FIXME
        chmod($filepath, 0666);
    }

    public function store(Uuid $uuid, ChunkMetadata $metadata, $content)
    {
        $filepath = $this->computeFilepath($uuid);

        if(! $stream = fopen($filepath, 'r+'))
        {
            throw new \RuntimeException("Unable to open file $filepath");
        }

        fseek($stream, $metadata->offset());
        fwrite($stream, $content);
        fclose($stream);
    }

    public function finish(ChunkedMessageMetadata $metadata, array $headers)
    {
        $filepath = $this->computeFilepath($metadata->uuid());
        $checksum = sha1_file($filepath);

        if($checksum !== $metadata->checksum())
        {
            unlink($filepath);
            throw new \RuntimeException("Invalid checksum for chunked message " . $metadata->uuid());
        }

        if(isset($headers['file']))
        {
            if(isset($headers['file']['filename']))
            {
                rename($filepath, $this->tempDirPath . $headers['file']['filename']);
            }
        }
    }
}
