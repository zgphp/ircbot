<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Plugin;

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
    }

    public function onEndMotd($message, $write)
    {
        $channels = $this->settings['autojoin']['channels'];

        if (is_string($channels)) {
            $channels = array($channels);
        }

        foreach($channels as $channel) {
            $write->ircJoin($channel);
        }
    }
}
