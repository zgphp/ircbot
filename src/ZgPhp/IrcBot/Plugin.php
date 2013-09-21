<?php

namespace ZgPhp\IrcBot;

use Monolog\Logger;

/** Base class for plugins. */
class Plugin
{
    protected $settings;
    protected $log;

    public function __construct(array $settings, Logger $log)
    {
        $this->settings = $settings;
        $this->log = $log;
        $this->init();
    }

    protected function init()
    {

    }

    public function onPrivMsg($message, $write)
    {

    }

    public function onPing($message, $write)
    {

    }

    public function onEndMotd($message, $write)
    {

    }

    /** Called by Client when a message is recieved. */
    public function dispatch($message, $write, $connection)
    {
        if (isset($message['command'])) {
            switch($message['command']) {
                case "PING":
                    $this->onPing($message, $write);
                    break;

                case 376: // RPL_ENDOFMOTD
                case 422: // ERR_NOMOTD
                    $this->onEndMotd($message, $write);
                    break;

                case "PRIVMSG":
                    $this->onPrivMsg($message, $write);
                    break;
            }
        }
    }

    /** Returns the specified value from settings. */
    protected function getSetting(array $path, $optional = false)
    {
        $settings = $this->settings;
        foreach($path as $item) {
            if (!isset($settings[$item])) {
                if ($optional) {
                    return null;
                } else {
                    $logPath = implode('.', $path);
                    throw new \Exception("Required setting [$logPath] not found.");
                }
            }

            $settings = $settings[$item];
        }

        return $settings;
    }
}