<?php

namespace ZgPhp\IrcBot;

use ZgPhp\IrcBot\Plugin;
use ZgPhp\IrcBot\Event;

/** Base class for plugins which are triggered by a PrivMsg of a certain pattern. */
abstract class MessagePatternPlugin extends Plugin
{
    /** The pattern to match against. */
    protected $pattern;

    /** Parse priv messages an look for those matching the pattern. */
    public function onPrivMsg(Event $event)
    {
        $text = $event->message['params']['text'];

        if (preg_match($this->pattern, $text, $matches)) {
            $this->handle($event, $matches);
        }
    }

    /** Handler for messages which match the pattern. */
    abstract protected function handle(Event $event, $matches);
}
