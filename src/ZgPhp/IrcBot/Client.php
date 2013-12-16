<?php

namespace ZgPhp\IrcBot;

use Monolog\Logger;

use Evenement\EventEmitter;

use Phergie\Irc\Client\React\Client as PhergieClient;
use Phergie\Irc\Client\React\WriteStream;
use Phergie\Irc\Connection;
use Phergie\Irc\ConnectionInterface;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class Client extends EventEmitter
{
    /**
     * The Phergie IRC client.
     * @var Phergie\Irc\Client\React\Client
     */
    private $client;

    /**
     * Phergie cnnection object, holds connection data.
     * @var Phergie\Irc\Connection
     */
    private $connection;

    /**
     * The logger
     * @var Monolog\Logger
     */
    private $log;

    /** Array of active plugins. */
    private $plugins = array();

    /** Decoded settings from settings.yml. */
    private $settings;

    public function __construct($settings)
    {
        $this->client = new PhergieClient();
        $this->log = $this->client->getLogger();

        $this->settings = $this->configure($settings);
        $this->connection = $this->createConnection();

        $this->configurePlugins();
        $this->setupBindings();
    }

    /** Connects to server. */
    public function run()
    {
        $this->client->run($this->connection);
    }

    private function configure($settings)
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('settings');

        // Define client configuration values
        $rootNode->children()
            ->scalarNode('server_host')
                ->isRequired()->cannotBeEmpty()->end()
            ->integerNode('server_port')
                ->cannotBeEmpty()->defaultValue(6667)->end()
            ->scalarNode('nickname')
                ->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('realname')
                ->defaultValue("")->end()
            ->scalarNode('username')
                ->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('password')
                ->defaultValue("")->end();

        // Add plugin configuration values to tree
        if (isset($settings['plugins'])) {
            $pluginsNode = $rootNode->children()->arrayNode('plugins');

            foreach(array_keys($settings['plugins']) as $name) {
                $pluginNode = $pluginsNode->children()->arrayNode($name);
                $plugin = $this->getPluginInstance($name);
                $plugin->addConfig($pluginNode);

                $this->plugins[$name] = $plugin;
            }
        }

        // Process and validate the configuration
        $tree = $treeBuilder->buildTree();
        $proc = new Processor();
        return $proc->process($tree, [$settings]);
    }

    private function configurePlugins()
    {
        foreach ($this->plugins as $name => $plugin) {
            $config = $this->settings['plugins'][$name];
            $plugin->configure($config);
        }
    }

    private function getPluginInstance($name)
    {
        // Change name to camel case to form plugin class name
        $className = str_replace('_', ' ', $name);
        $className = ucwords($className);
        $className = str_replace(' ', '', $className);

        // Add namespace
        $className = __NAMESPACE__ . "\\Plugins\\" . $className;

        if (!class_exists($className)) {
            throw new \Exception("Plugin class not found: $className");
        }

        return new $className($this, $this->log);
    }

    /** Creates a Connection object from data in settings. */
    protected function createConnection()
    {
        $connection = new Connection();
        $connection->setServerHostname($this->settings['server_host']);
        $connection->setServerPort($this->settings['server_port']);
        $connection->setNickname($this->settings['nickname']);
        $connection->setRealname($this->settings['realname']);;
        $connection->setUsername($this->settings['username']);
        $connection->setPassword($this->settings['password']);

        return $connection;
    }

    protected function setupBindings()
    {
        $this->client->on('irc.received', array($this, 'handleReceived'));

        // Reconnect on failed connection
        $this->client->on('connect.error', function($message, $connection, $logger) {
            $host = $connection->getServerHostname();
            $logger->debug("Connection to $host lost, attempting to reconnect\n");
            $this->client->addConnection($connection);
        });
    }

    /** Maps IRC commands to events to be emitted. */
    private $commandMap = array(
        "PING"    => "ircbot.ping",
        376       => "ircbot.endofmotd", // RPL_ENDOFMOTD
        422       => "ircbot.endofmotd", // ERR_NOMOTD
        "PRIVMSG" => "ircbot.privmsg"
    );

    /**
     * Handler for "irc.recieved".
     */
    public function handleReceived(array $message, WriteStream $write, ConnectionInterface $connection, Logger $logger)
    {
        if (isset($message['command'])) {
            $cmd = $message['command'];
            if (isset($this->commandMap[$cmd])) {
                $event = new Event($message, $write, $connection, $logger);
                $eventID = $this->commandMap[$cmd];

                $this->emit($eventID, array($event));
            }
        }
    }

    /**
     * Returns the underlying event loop implementation.
     * @return React\EventLoop\LoopInterface
     */
    public function getLoop()
    {
        return $this->client->getLoop();
    }
}
