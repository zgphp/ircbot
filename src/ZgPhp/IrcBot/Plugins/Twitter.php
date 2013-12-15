<?php

namespace ZgPhp\IrcBot\Plugins;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

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

    public function addConfig(ArrayNodeDefinition $node)
    {
        $node->children()
            ->scalarNode("channel")
                ->isRequired()->cannotBeEmpty()->end()
            ->integerNode("delay")
                ->cannotBeEmpty()->defaultValue(60)->end()
            ->scalarNode("query")
                ->isRequired()->cannotBeEmpty()->end()

            ->scalarNode("consumer_key")
                ->isRequired()->cannotBeEmpty()->end()
            ->scalarNode("consumer_secret")
                ->isRequired()->cannotBeEmpty()->end()
            ->scalarNode("access_token")
                ->isRequired()->cannotBeEmpty()->end()
            ->scalarNode("access_token_secret")
                ->isRequired()->cannotBeEmpty()->end()
        ;
    }

    public function configure(array $settings = null)
    {
        $this->channel = $settings['channel'];
        $this->delay = $settings['delay'];
        $this->query = $settings['query'];

        $this->twitter = new \Twitter(
            $settings['consumer_key'],
            $settings['consumer_secret'],
            $settings['access_token'],
            $settings['access_token_secret']
        );

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
        try {
            $data = $this->searchTweets();
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            $this->log->warn("Twitter plugin: Failed fetching tweets. Error: $msg\n");
            return;
        }

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
