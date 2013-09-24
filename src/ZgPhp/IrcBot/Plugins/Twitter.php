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

    /** How often to look for tweets. */
    private $delay;

    /** ID of the last tweet posted to IRC. */
    private $sinceID;

    /** Channel to write to. */
    private $channel;

    protected function init()
    {
        $consumerKey = $this->getSetting(array('twitter', 'consumer_key'));
        $consumerSecret = $this->getSetting(array('twitter', 'consumer_secret'));
        $accessToken = $this->getSetting(array('twitter', 'access_token'));
        $accessTokenSecret = $this->getSetting(array('twitter', 'access_token_secret'));

        $this->channel = $this->getSetting(array('twitter', 'channel'));
        $this->delay = $this->getSetting(array('twitter', 'delay'), true, 60);
        $this->query = $this->getSetting(array('twitter', 'query'));

        $this->twitter = new \Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);

        // Get the last mention and remember it's id
        $data = $this->twitter->request('statuses/mentions_timeline', 'GET', array('count' => 1));
        if (!empty($data)) {
            $mention = $data[0];
            $this->sinceID = $mention->id_str;
        }
    }

    /** Activate after MOTD has been recieved. */
    public function onEndMotd(Event $event)
    {
        $this->log->debug("Twitter plugin: setting up periodic timer with delay of $this->delay seconds.");
        $loop = $this->getClient()->getLoop();
        $loop->addPeriodicTimer($this->delay, array($this, 'findMentions'));
    }

    public function findMentions()
    {
        $args = array(
            'q' => $this->query,
            'since_id' => $this->sinceID,
        );

        $data = $this->twitter->request('search/tweets', 'GET', $args);
        if (empty($data)) {
            return;
        }

        $count = count($data->statuses);
        $this->log->debug("Twitter plugin: Found $count mentions.");

        foreach($data->statuses as $status) {
            $id = $status->id_str;
            $text = $status->text;
            $user = $status->user->screen_name;

            // Skip retweets
            if(isset($status->retweeted_status)) {
                continue;
            }

            $url = "https://twitter.com/$user/status/$id";
            $text = "Tweet from @$user: $text .::. $url";

            $write = $this->getClient()->getWriteStream();
            $write->ircPrivMsg($this->channel, $text);
        }

        $this->sinceID = $data->search_metadata->max_id_str;
    }
}
