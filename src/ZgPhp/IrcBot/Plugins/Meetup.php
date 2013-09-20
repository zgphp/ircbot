<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\MessagePatternPlugin;

/**
 * Displays ZgPHP meetup data from meetup.com.
 *
 * Required settings:
 *
 * meetup:
 *     api_key: 123456
 *
 * Get the API key from:
 * http://www.meetup.com/meetup_api/key/
 */
class Meetup extends MessagePatternPlugin
{
    protected $pattern = '/^!meetup (.+)$/i';

    const API_BASE = "http://api.meetup.com/2/";

    const ZGPHP_GROUP_ID = 8328272;

    /** The meetup.com api access key, read from settings (meetup.api_key).*/
    private $apiKey;

    protected function init()
    {
        if (!isset($this->settings['meetup']['api_key'])) {
            throw new \Exception("Missing meetup.api_key setting.");
        }
        $this->apiKey = $this->settings['meetup']['api_key'];
    }

    protected function handle($message, $matches, $write)
    {
        $command = strtolower(trim($matches[1]));

        switch($command) {
            case "next":
                $this->handleNext($message, $write);
                break;
            default:
                $this->showUsage($message, $write);
        }
    }

    protected function showUsage($message, $write)
    {
        $channel = $message['params']['receivers'];
        $write->ircPrivmsg($channel, "Meetup plugin usage:");
        $write->ircPrivmsg($channel, "!meetup next - show next meetup info");
    }

    protected function handleNext($message, $write)
    {
        $data = $this->fetchPendingMeetups();
        $meetups = $data->results;

        if (empty($meetups)) {
            $write->ircPrivmsg($channel, "Next meetup not scheduled.");
            return;
        }

        // Meetups are sorted by time
        $meetup = $meetups[0];

        if ($meetup->visibility !== 'public') {
            $write->ircPrivmsg($channel, "Next meetup not scheduled.");
            return;
        }

        $text = $this->parseMeetup($meetup);
        $channel = $message['params']['receivers'];
        $write->ircPrivmsg($channel, $text);
    }

    /** Formats the meetup data to a string. */
    protected function parseMeetup($meetup)
    {
        $venue = array($meetup->venue->name);

        if (isset($meetup->venue->address_1)) {
            $venue[] = $meetup->venue->address_1;
        }

        if (isset($meetup->venue->address_2)) {
            $venue[] = $meetup->venue->address_2;
        }

        if (isset($meetup->venue->address_3)) {
            $venue[] = $meetup->venue->address_3;
        }

        if (isset($meetup->venue->city)) {
            $venue[] = $meetup->venue->city;
        }

        $venue = implode(', ', $venue);

        $attending = $meetup->yes_rsvp_count;
        $date = date('d.m.Y', $meetup->time / 1000);
        $time = date('H:i', $meetup->time / 1000);
        $name = $meetup->name;
        $url = $meetup->event_url;

        return "Next meetup: $name on $date starting from $time at $venue. " .
            "Attending: $attending developers. Details and RSVP: $url";
    }

    protected function fetchPendingMeetups()
    {
        $url = self::API_BASE . 'events?';
        $params = [
            'group_id' => self::ZGPHP_GROUP_ID,
            'key' => $this->apiKey,
            'status' => 'upcoming'
        ];
        $url .= http_build_query($params);

        $data = file_get_contents($url);
        if ($data === false) {
            throw new \Exception("Failed loading meetup.com data.");
        }

        $data = json_decode($data);
        if ($data === false) {
            throw new \Exception("Failed decoding meetup.com data.");
        }

        return $data;
    }
}
