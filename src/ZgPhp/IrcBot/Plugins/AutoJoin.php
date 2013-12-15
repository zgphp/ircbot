<?php

namespace ZgPhp\IrcBot\Plugins;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

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

    public function addConfig(ArrayNodeDefinition $node)
    {
        $node->children()
            ->arrayNode("channels")
                ->prototype("scalar");
    }

    public function configure(array $settings = null)
    {
        $this->channels = $settings['channels'];
    }

    public function onEndMotd(Event $event)
    {
        foreach($this->channels as $channel) {
            $event->write->ircJoin($channel);
        }
    }
}
