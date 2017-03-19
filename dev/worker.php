<?php

require __DIR__ . '/../vendor/autoload.php';

use Puzzle\Configuration\Memory;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Workers\WorkerContext;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Workers\Worker;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\AMQP\Workers\ProcessorInterfaceAdapter;
use Puzzle\AMQP\Consumers\Insomniac;

$configuration = new Memory(array(
    'amqp/broker/host' => 'rabbitmq',
    'amqp/broker/login' => 'guest',
    'amqp/broker/password' => 'guest',
    'amqp/broker/vhost' => '/',
    'amqp/global/disallowSilentDropping' => true,
    'app/id' => 'puzzle-amqp-test',
));

$client = new Pecl($configuration);
$consumer = new Insomniac();

class TestWorker implements Worker
{
    use LoggerAwareTrait;

    private
        $nbMessages;
    
    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->nbMessages = 0;
    }

    public function process(ReadableMessage $message)
    {
        $decoded = [
            "content-type" => $message->getContentType(),
            "body" => $message->getBodyInOriginalFormat(),
            "headers" => $message->getHeaders()
        ];
        
        var_dump($decoded);
     
        $this->nbMessages++;
    }
    
    public function nbMessagesProcessed()
    {
        return $this->nbMessages;
    }
}

$workerContext = new WorkerContext(function() {
        return new TestWorker();
    },
    $consumer,
    'test_1'
);

$processor = new ProcessorInterfaceAdapter($workerContext);

$workerContext
    ->getConsumer()
    ->consume($processor, $client, $workerContext);

$nbMsg = $workerContext->getWorker()->nbMessagesProcessed();
if($nbMsg > 0)
{
    echo "\033[32m[SUCCESS] WORKER HAS PROCESSED $nbMsg MESSAGE(S) \033[0m" . PHP_EOL;
    return;
}

echo "\033[31m[FAILD] WORKER HAS PROCESSED NONE MESSAGE \033[0m" . PHP_EOL;
