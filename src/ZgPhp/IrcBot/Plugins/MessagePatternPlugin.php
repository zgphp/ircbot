<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Plugin;

/** Base class for plugins which are triggered by a PrivMsg of a certain pattern. */
abstract class MessagePatternPlugin extends Plugin
{
    /** The pattern to match against. */
    protected $pattern;

    /** Parse priv messages an look for those matching the pattern. */
    public function onPrivMsg($message, $write)
    {
        $text = $message['params']['text'];

        if (preg_match($this->pattern, $text, $matches)) {
            $this->handle($message, $matches, $write);
        }
    }

    /** Handler for messages which match the pattern. */
    abstract protected function handle($message, $matches, $write)
    {

    }
}
