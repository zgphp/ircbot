<?php

/*
 * Sends PONG after recieving PING to keep the connection alive.
 */

$client->on('irc.received', function($message, $write, $connection, $logger) {
    if (isset($message['command']) && $message['command'] == 'PING') {
        $loger->debug("Sending pong");
        $write->ircPong('zgphpbot');
    }
});
