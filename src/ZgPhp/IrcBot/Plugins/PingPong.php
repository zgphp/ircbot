<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Plugin;

/**
 * Responds to server PINGs with PONGs.
 */
class PingPong extends Plugin
{
    public function onPing($message, $write)
    {
        $write->ircPong('zgphpbot');
    }
}
