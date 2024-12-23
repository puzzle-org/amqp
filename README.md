Puzzle AMQP  ![PHP >= 8.0](https://img.shields.io/badge/php-%3E%3D%208.0-blue.svg)
===========
PHP 5.6 & 7.x users, please use < 5.x versions.

QA
--

| Service                 | Result |
|-------------------------| --- |
| **CI** (PHP 8.3 .. 8.4) | [![CI](https://github.com/puzzle-org/configuration/actions/workflows/ci.yml/badge.svg)](https://github.com/puzzle-org/amqp/actions/workflows/ci.yml)
| **Packagist**           | [![Latest Stable Version](https://poser.pugx.org/puzzle/amqp/v/stable.png)](https://packagist.org/packages/puzzle/amqp) [![Total Downloads](https://poser.pugx.org/puzzle/amqp/downloads.svg)](https://packagist.org/packages/puzzle/amqp) |

Configuration
-------------

```yml
# amqp.yml
broker:
    host: myRabbit
    port: 5672
    login: guest
    password: guest
    vhost: /
global:
    disallowSilentDropping: false

# app.yml
id: myApp
```

Usage
-----
## Sending a message

```php
<?php

require '../vendor/autoload.php';

use Puzzle\Configuration\Memory;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Messages\Message;

$configuration = new Memory(array(
    'amqp/broker/host' => 'myRabbit',
    'amqp/broker/login' => 'guest',
    'amqp/broker/password' => 'guest',
    'amqp/broker/vhost' => '/',
    'app/id' => 'myApp',
));

$client = new Pecl($configuration);

$message = new Message('my.routing.key');
$message->setJson([
    'key' => 'value',
    'key2' => 'value2',
]);

$client->publish('myExchange', $message);
```
## Consuming a message

### Worker declaration :
```php
<?php

use Puzzle\AMQP\Consumers;
use Puzzle\AMQP\Clients;
use Puzzle\AMQP\Workers\ProcessorInterfaceAdapter;
use Puzzle\Configuration\Memory;

$configuration = new Memory(array(
    'amqp/broker/host' => 'rabbitmq',
    'amqp/broker/login' => 'guest',
    'amqp/broker/password' => 'guest',
    'amqp/broker/vhost' => '/',
    'app/id' => 'myApp',
));

$consumer = new Consumers\Simple();

$worker = new ExampleWorker();

$consumer->consume(
    new ProcessorInterfaceAdapter($worker),
    new Clients\Pecl($configuration),
    'queue.name'
);
```
### Worker example :
```php
<?php

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Puzzle\AMQP\ReadableMessage;
use Puzzle\AMQP\Workers\Worker;

class ExampleWorker implements Worker
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function process(ReadableMessage $message): void
    {
        // your code here
    }
}
```

BC Breaks changelog
-------------------
**4.x -> 5.x**

 - Drop support for php 5.6 & 7.x
 - Worker now catch Throwable, not only Exceptions
- Consumer / Worker / WorkerContext signature change

**3.x -> 4.x**

 - Chunk management introduced in 3.1 has been refactored and made easier : just use Streamed* bodies and same client as usual 

**2.x -> 3.x**

 - Message hooks has been removed
 - Raw & Json implementations of WritableMessage has been replaced by Message + implementations of Body (Text, Json, Binary)
 - Message interface has been renamed into MessageMetadata
 - Some specific features removed from WorkerContext
 - Getter for specific headers removed from MessageAdapter
 - Message InMemory implementation (for unit testing purpose) has been changed
 - MessageAdapter must not be directly constructed anymore, use MessageAdapterFactory instead

**1.x -> 2.x**

 - Drop support for Silex 1.x (in favor of Silex 2.x)
