<?php

namespace ZgPhp\IrcBot;

use Monolog\Logger;
use ZgPhp\IrcBot\Client;

/** Base class for plugins. */
class Plugin
{
    protected $client;
    protected $settings;
    protected $log;

    public function __construct(Client $client, array $settings, Logger $log)
    {
        $this->client = $client;
        $this->settings = $settings;
        $this->log = $log;

        $this->init();
    }

    protected function init()
    {

    }

    public function onPrivMsg(Event $event)
    {

    }

    public function onPing(Event $event)
    {

    }

    public function onEndMotd(Event $event)
    {

    }

    /** Called by Client when a message is recieved. */
    public function dispatch(Event $event)
    {
        switch($event->getCommand()) {

            case "PING":
                $this->onPing($event);
                break;

            case 376: // RPL_ENDOFMOTD
            case 422: // ERR_NOMOTD
                $this->onEndMotd($event);
                break;


            case "PRIVMSG":
                $this->onPrivMsg($event);
                break;
        }
    }

    /** Returns the specified value from settings. */
    protected function getSetting(array $path, $optional = false, $default = null)
    {
        $settings = $this->settings;
        foreach($path as $item) {
            if (!isset($settings[$item])) {
                if ($optional) {
                    return $default;
                } else {
                    $logPath = implode('.', $path);
                    throw new \Exception("Required setting [$logPath] not found.");
                }
            }

            $settings = $settings[$item];
        }

        return $settings;
    }

    protected function getClient()
    {
        return $this->client;
    }
}