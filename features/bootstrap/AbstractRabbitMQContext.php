<?php

namespace Puzzle\AMQP\Contexts;

use Behat\Behat\Context\Context;
use Puzzle\Configuration\Yaml;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use RabbitMQ\Management\APIClient;
use Puzzle\AMQP\Clients\Pecl;
use Puzzle\AMQP\Messages\Processors\GZip;

abstract class AbstractRabbitMQContext implements Context
{
    const
        TEXT_ROUTING_KEY = 'normal.text.key',
        XML_ROUTING_KEY = 'normal.xml.key',
        JSON_ROUTING_KEY = 'normal.json.key',
        COMPRESSED_ROUTING_KEY = 'zip.text.key';
    
    protected
        $api,
        $exchange,
        $client,
        $configuration;
    
    public function __construct($path)
    {
        $this->configuration = new Yaml(new Filesystem(new Local($path)));
        $this->exchange = 'puzzle';

        $this->api = APIClient::factory(['host' => $this->host()]);

        $this->client = new Pecl($this->configuration);
        $this->client->appendMessageProcessor(new GZip());
    }
    
    private function host()
    {
        return $this->configuration->readRequired('amqp/broker/host');
    }
    
    protected function vhost()
    {
        return $this->configuration->readRequired('amqp/broker/vhost');
    }
}
