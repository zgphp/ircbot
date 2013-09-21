<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Plugin;
use ZgPhp\IrcBot\Event;

/**
 * AutoJoin plugin
 *
 * Automatically joins one or more channels after the MOTD message has been
 * recieved from the server.
 *
 * Settings:
 * ```
 * autojoin:
 *     channels:
 *         - #zgphp
 *         - #webcampzg
 *         - #other
 * ```
 */
class AutoJoin extends Plugin
{
    private $channels;

    protected function init()
    {
        $this->channels = $this->getSetting(array('autojoin', 'channels'));

        if (is_string($this->channels)) {
            $this->channels = array($this->channels);
        }
    }

    public function onEndMotd(Event $event)
    {
        foreach($this->channels as $channel) {
            $event->write->ircJoin($channel);
        }
    }
}
