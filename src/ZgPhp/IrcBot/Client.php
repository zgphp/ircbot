<?php

namespace ZgPhp\IrcBot;

use Phergie\Irc\Client\React\Client as PhergieClient;
use Phergie\Irc\Connection;

class Client
{
    private $client;
    private $connection;
    private $log;
    private $plugins = array();
    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->client = new PhergieClient();
        $this->connection = $this->createConnection($settings);
        $this->log = $this->client->getLogger();
        $this->setupPlugins();
        $this->setupBindings();
    }

    public function run()
    {
        $this->client->run($this->connection);
    }

    /** Creates a Connection object from data in settings. */
    protected function createConnection($settings)
    {
        $connection = new Connection();

        if (isset($settings['server_host'])) {
            $connection->setServerHostname($settings['server_host']);
        }

        if (isset($settings['server_port'])) {
            $connection->setServerPort($settings['server_port']);
        }

        if (isset($settings['nickname'])) {
            $connection->setNickname($settings['nickname']);
        }

        if (isset($settings['realname'])) {
            $connection->setRealname($settings['realname']);;
        }

        if (isset($settings['username'])) {
            $connection->setUsername($settings['username']);
        }

        if (isset($settings['password'])) {
            $connection->setPassword($settings['password']);
        }

        return $connection;
    }

    protected function setupBindings()
    {
        $this->client->on('irc.received', array($this, 'handleReceived'));
    }

    protected function setupPlugins()
    {
        if (empty($this->settings['plugins'])) {
            return;
        }

        foreach($this->settings['plugins'] as $key => $class) {
            $this->setupPlugin($class);
        }
    }

    protected function setupPlugin($class)
    {
        $this->log->info("Activating plugin: $class\n");

        // Try default namespace
        $class = __NAMESPACE__ . '\\Plugins\\' . $class;

        if (!class_exists($class)) {
            $this->log->error("Cannot find plugin class [$class]. Skipping.\n");
            return;
        }

        $plugin = new $class($this, $this->settings, $this->log);

        if (!($plugin instanceof Plugin)) {
            $this->log->error("Plugin class [$class] is not subclass of ZgPHP\\IrcBot\\Plugin. Skipping.\n");
            return;
        }

        $this->plugins[] = $plugin;
    }

    public function handleReceived($message, $write, $connection, $logger)
    {
        $event = new Event($message, $write, $connection, $logger);

        foreach($this->plugins as $plugin) {
            $plugin->dispatch($event);
        }
    }

    public function getLoop()
    {
        return $this->client->getLoop();
    }

    public function getWriteStream()
    {
        return $this->client->getWriteStream();
    }
}
