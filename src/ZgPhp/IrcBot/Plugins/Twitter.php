<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Plugin;
use ZgPhp\IrcBot\Event;

/**
 * Twitter plugin
 *
 * Looks for tweets matching a search query and posts them to the channel.
 *
 * Settings:
 * ```
 * twitter:
 *     consumer_key:
 *     consumer_secret:
 *     access_token:
 *     access_token_secret:
 *
 *     query: "#zgphp or @zgphp"
 *     delay: 60 # seconds
 *     channel: "#zgphp"
 * ```
 */
class Twitter extends Plugin
{
    const DEFAULT_DELAY = 60;

    /**
     * The twitter client lib.
     * @see https://github.com/dg/twitter-php
     */
    private $twitter;

    /**
     * The Twitter search query.
     * @see https://dev.twitter.com/docs/using-search
     */
    private $query;

    /** How often to look for new tweets. */
    private $delay;

    /** ID of the last tweet posted to IRC. */
    private $sinceID;

    /** IRC channel to write to (e.g. "#zgphp"). */
    private $channel;

    /**
     * The write stream.
     * @var Phergie\Irc\Client\React\WriteStream
     */
    private $write;

    protected function init()
    {
        $this->channel = $this->getSetting(array('twitter', 'channel'));
        $this->delay = $this->getSetting(array('twitter', 'delay'), true, self::DEFAULT_DELAY);
        $this->query = $this->getSetting(array('twitter', 'query'));

        $consumerKey = $this->getSetting(array('twitter', 'consumer_key'));
        $consumerSecret = $this->getSetting(array('twitter', 'consumer_secret'));
        $accessToken = $this->getSetting(array('twitter', 'access_token'));
        $accessTokenSecret = $this->getSetting(array('twitter', 'access_token_secret'));

        $this->twitter = new \Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);

        // Get the most recent tweet's ID
        $data = $this->searchTweets();
        $this->sinceID = $data->search_metadata->max_id_str;
    }

    /** Activate after MOTD has been recieved. */
    public function onEndMotd(Event $event)
    {
        $this->log->debug("Twitter plugin: setting up periodic timer with delay of $this->delay seconds.\n");

        // Save the write stream so the plugin can write on demand.
        // Not the most elegant solution, but best I could come up with.
        $this->write = $event->write;

        $loop = $this->getClient()->getLoop();
        $loop->addPeriodicTimer($this->delay, array($this, 'processTweets'));
    }

    /**
     * Loads recent tweets which match the query and posts them to the
     * IRC channel.
     */
    public function processTweets()
    {
        $data = $this->searchTweets();

        $count = count($data->statuses);
        if ($count > 0) {
            $this->log->debug("Twitter plugin: Found $count mentions.\n");
        }

        foreach($data->statuses as $tweet) {
            $text = $tweet->text;
            $user = $tweet->user->screen_name;
            $url = $this->getTweetURL($tweet);

            // Skip retweets
            if(isset($tweet->retweeted_status)) {
                continue;
            }

            $text = "@$user tweeted: $text .::. $url";
            $this->write->ircPrivMsg($this->channel, $text);
        }

        $this->sinceID = $data->search_metadata->max_id_str;
    }

    /** Loads tweets matching the query since the last processed tweet. */
    public function searchTweets()
    {
        $args = array(
            'q' => $this->query,
            'since_id' => $this->sinceID,
            'result_type' => 'recent', // return only the most recent results in the response
        );

        return $this->twitter->request('search/tweets', 'GET', $args);
    }

    private function getTweetURL($tweet)
    {
        $id = $tweet->id_str;
        $user = $tweet->user->screen_name;
        return "https://twitter.com/$user/status/$id";
    }
}
