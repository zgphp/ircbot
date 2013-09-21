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