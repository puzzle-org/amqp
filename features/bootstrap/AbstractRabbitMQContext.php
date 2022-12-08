<?php

namespace Puzzle\AMQP\Contexts;

use Behat\Behat\Context\Context;
use Puzzle\Configuration\Yaml;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Messages\Processors\GZip;

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
    protected \RabbitMqHttpApiClient
        $httpClient;
    protected string
        $vhost;

    public function __construct($path)
    {
        $configuration = new Yaml(new Filesystem(new Local($path)));

        $this->client = new Pecl($configuration);
        $this->client->appendMessageProcessor(new GZip());

        $rabbitConf = $configuration->readRequired('amqp/broker');
        $this->httpClient = new \RabbitMqHttpApiClient(
            $rabbitConf['host'],
            15672,
            $rabbitConf['login'],
            $rabbitConf['password'],
        );
        $this->vhost = $rabbitConf['vhost'];
    }
}
