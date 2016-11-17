Puzzle AMQP
===========

Configuration
-------------

```yml
# amqp.yml
broker:
    host: myRabbit
    login: guest
    password: guest
    vhost: /

# app.yml
id: myApp
```

Usage
-----

```php
<?php

require '../vendor/autoload.php';

use Puzzle\Configuration\Memory;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Messages\Json;

$configuration = new Memory(array(
    'amqp/broker/host' => 'myRabbit',
    'amqp/broker/login' => 'guest',
    'amqp/broker/password' => 'guest',
    'amqp/broker/vhost' => '/',
    'app/id' => 'myApp',
));

$client = new Pecl($configuration);

$message = new Json('my.routing.key');
$message->setBody([
    'key' => 'value',
    'key2' => 'value2',
]);

$client->publish('myExchange', $message);
```

