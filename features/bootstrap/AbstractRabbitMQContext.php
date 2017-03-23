<?php

namespace Puzzle\AMQP\Contexts;

use Behat\Behat\Context\Context;
use Puzzle\Configuration\Yaml;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use RabbitMQ\Management\APIClient;
use Puzzle\AMQP\Clients\Pecl;

abstract class AbstractRabbitMQContext implements Context
{
    const
        TEXT_ROUTING_KEY = 'text.key',
        JSON_ROUTING_KEY = 'json.key';
    
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
