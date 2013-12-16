<?php

namespace ZgPhp\IrcBot;

use Monolog\Logger;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

use ZgPhp\IrcBot\Client;

/** Base class for plugins. */
class Plugin
{
    protected $client;
    protected $settings;
    protected $log;

    public function __construct(Client $client, Logger $log)
    {
        $this->client = $client;
        $this->log = $log;
    }

    /** Sets up the plugin with values from the config file. */
    public function configure(array $settings = null)
    {

    }

    /** Adds configuration definitions for values used by the plugin. */
    public function addConfig(ArrayNodeDefinition $node)
    {

    }

    protected function getClient()
    {
        return $this->client;
    }
}