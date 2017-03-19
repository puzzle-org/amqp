<?php

require __DIR__ . '/../vendor/autoload.php';

use Puzzle\Configuration\Memory;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Messages\Message;

$configuration = new Memory(array(
    'amqp/broker/host' => 'rabbitmq',
    'amqp/broker/login' => 'guest',
    'amqp/broker/password' => 'guest',
    'amqp/broker/vhost' => '/',
    'amqp/global/disallowSilentDropping' => true,
    'app/id' => 'puzzle-amqp-test',
));

$client = new Pecl($configuration);

$messages = [
    (new Message('burgers.over.ponies'))->setJson([
        'meat' => 'beef',
        'with' => 'fries',
    ]),
    (new Message('burgers.over.ponies'))->setText("One Julian McDeluxe please !"),
    (new Message('burgers.over.ponies'))->setBinary("\x04\x00\xa0\x00"),
];

$result = true;
foreach($messages as $message)
{
    $result &= $client->publish('puzzle', $message);
}

if($result == true)
{
    echo "\033[32m[SUCCESS] MESSAGE SENT \033[0m" . PHP_EOL;
    return;
}

echo "\033[31m[FAIL] MESSAGE WAS NOT PUBLISHED\033[0m" . PHP_EOL;
