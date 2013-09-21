<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Plugin;
use ZgPhp\IrcBot\Event;

/**
 * Responds to server PINGs with PONGs.
 */
class PingPong extends Plugin
{
    public function onPing(Event $event)
    {
        $event->write->ircPong('zgphpbot');
    }
}
