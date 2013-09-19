<?php

/*
 * Automatically joins one or more channels on connect.
 *
 * Settings:
 *     $settings['autojoin'] - an array of channels to join (required)
 *
 * Example:
 *     $settings = [
 *         'autojoin' => ['#one', '#another']
 *     ]
 */

// Check for required settings
if (!isset($settings['autojoin'])) {
    $logger->error("Autojoin plugin requires 'autojoin' settings value.\n");
    return;
}

$client->on('irc.received', function($message, $write, $connection, $logger) use ($settings) {
    if (isset($message['code'])) {
        if (in_array($message['code'], ['RPL_ENDOFMOTD', 'ERR_NOMOTD'])) {
            $channels = $settings['autojoin'];

            if (is_string($channels)) {
                $channels = array($channels);
            }

            foreach($channels as $channel) {
                $write->ircJoin($channel);
            }
        }
    }
});
