<?php

namespace Puzzle\AMQP\Contexts;

use Behat\Behat\Context\Context;
use Puzzle\Configuration\Yaml;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use RabbitMQ\Management\APIClient;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Messages\Processors\GZip;
use Puzzle\Configuration;

abstract class AbstractRabbitMQContext implements Context
{
    protected const
        TEXT_ROUTING_KEY = 'normal.text.key',
        XML_ROUTING_KEY = 'normal.xml.key',
        JSON_ROUTING_KEY = 'normal.json.key',
        COMPRESSED_ROUTING_KEY = 'zip.text.key',
        EXCHANGE = 'puzzle';
    
    protected Pecl
        $client;

    public function __construct($path)
    {
        $configuration = new Yaml(new Filesystem(new Local($path)));

        $this->client = new Pecl($configuration);
        $this->client->appendMessageProcessor(new GZip());
    }
}
