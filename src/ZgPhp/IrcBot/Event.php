<?php

namespace ZgPhp\IrcBot;

/**
 * Encapsulates data related to an IRC event.
 */
class Event
{
    /**
     * The write stream
     * @var Phergie\Irc\Client\React\WriteStream
     */
    public $write;

    /**
     * Connection object.
     * @var Phergie\Irc\ConnectionInterface
     */
    public $Connection;

    /**
     * Parsed message.
     * @var array
     */
    public $message;

    /**
     * The logger.
     * @var Monolog\Logger
     */
    public $logger;

    public function __construct($message, $write, $connection, $logger)
    {
        $this->message = $message;
        $this->write = $write;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * Returns the channel name if the message was sent to a channel, or the
     * nickname if it was a private message.
     */
    public function getSource()
    {
        // Check if there is a target and it's a channel name
        if (isset($this->message['targets'][0])) {
            $target = $this->message['targets'][0];
            if ($target[0] == "#") {
                return $target;
            }
        }

        // If not, it's a private message
        return $this->message['nick'];
    }

    /** Returns the message command if it exists, or NULL if it doesn't. */
    public function getCommand()
    {
        if (isset($this->message['command'])) {
            return $this->message['command'];
        }
    }

    /**
     * Sends a privmsg to event source.
     * @see  getSource()
     */
    public function reply($text)
    {
        $target = $this->getSource();
        $this->write->ircPrivmsg($target, $text);
    }
}
