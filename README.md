Puzzle AMQP  ![PHP >= 5.6](https://img.shields.io/badge/php-%3E%3D%205.6-blue.svg)
===========

QA
--

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d18675cd-9850-4115-af23-b1afa8a859bc/big.png)](https://insight.sensiolabs.com/projects/d18675cd-9850-4115-af23-b1afa8a859bc)

Service | Result
--- | ---
**Travis CI** (PHP 5.6 .. 7.1) | [![Build Status](https://travis-ci.org/puzzle-org/amqp.svg?branch=master)](https://travis-ci.org/puzzle-org/amqp)
**Scrutinizer** | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/puzzle-org/amqp/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/puzzle-org/amqp/?branch=master)
**Code coverage** | [![codecov](https://codecov.io/gh/puzzle-org/amqp/branch/master/graph/badge.svg)](https://codecov.io/gh/puzzle-org/amqp)
**Packagist** | [![Latest Stable Version](https://poser.pugx.org/puzzle/amqp/v/stable.png)](https://packagist.org/packages/puzzle/amqp) [![Total Downloads](https://poser.pugx.org/puzzle/amqp/downloads.svg)](https://packagist.org/packages/puzzle/amqp)


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


Changelog
---------

**1.x -> 2.x**

 - Drop support for Silex 1.x (in favor of Silex 2.x)
