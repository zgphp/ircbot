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
    protected $pattern = '/^!meetup(.*)$/i';

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
        $command = isset($matches[1]) ? $matches[1] : null;
        $command = strtolower(trim($command));

        switch($command) {
            case "next":
                $this->handleNext($message, $write);
                break;
            case "upcoming":
                $this->handleUpcoming($message, $write);
                break;
            default:
                $this->showUsage($message, $write);
        }
    }

    protected function showUsage($message, $write)
    {
        $channel = $message['params']['receivers'];
        $write->ircPrivmsg($channel, "Meetup plugin usage:");
        $write->ircPrivmsg($channel, "!meetup next - show details for next meetup");
        $write->ircPrivmsg($channel, "!meetup upcoming - show list of upcoming meetups");
    }

    protected function handleNext($message, $write)
    {
        $meetups = $this->fetchPendingMeetups();
        if (empty($meetups)) {
            $write->ircPrivmsg($channel, "Next meetup not scheduled.");
            return;
        }

        // Meetups are sorted by time, take first one
        $meetup = $meetups[0];

        if ($meetup->visibility !== 'public') {
            $write->ircPrivmsg($channel, "Next meetup not scheduled.");
            return;
        }

        $text = "Next meetup" . $this->parseMeetup($meetup);
        $channel = $message['params']['receivers'];
        $write->ircPrivmsg($channel, $text);
    }

    protected function handleUpcoming($message, $write)
    {
        $meetups = $this->fetchPendingMeetups();
        if (empty($meetups)) {
            $write->ircPrivmsg($channel, "No meetups are scheduled.");
            return;
        }

        $channel = $message['params']['receivers'];
        $write->ircPrivmsg($channel, "Upcoming ZgPHP meetups:");
        foreach($meetups as $meetup) {
            if ($meetup->visibility == 'public') {
                $text = $this->parseMeetupShort($meetup);
                $write->ircPrivmsg($channel, $text);
            }
        }
    }

    /** Formats the meetup data to a string. */
    protected function parseMeetup($meetup)
    {
        $attending = $meetup->yes_rsvp_count;
        $name = $meetup->name;
        $url = $meetup->event_url;

        $venue = $this->parseVenue($meetup->venue);
        $dt = $this->parseDateTime($meetup);
        $date = $dt->format('d.m.Y');
        $time = $dt->format('H:i');

        return "$name on $date starting from $time at $venue. " .
            "Attending: $attending developers. Details and RSVP: $url";
    }

    public function parseMeetupShort($meetup)
    {
        $name = $meetup->name;

        $venue = $this->parseVenue($meetup->venue);
        $dt = $this->parseDateTime($meetup);
        $date = $dt->format('d.m.Y');
        $time = $dt->format('H:i');

        return "$name: $date from $time at $venue";
    }

    /** Parses the venue data to a string. */
    protected function parseVenue($venue)
    {
        $data = array($venue->name);

        foreach(array('address_1', 'address_2', 'address_3', 'city') as $key) {
            if (!empty($venue->$key)) {
                $data[] = $venue->$key;
            }
        }

        return implode(', ', $data);
    }

    /** Returns the meetup datetime as a DateTime object. */
    protected function parseDateTime($meetup)
    {
        // Use time zone from meetup data so format() returns local time
        // instead of UTC.
        $timezone = new \DateTimeZone($meetup->timezone);

        $dateTime = new \DateTime();
        $dateTime->setTimestamp($meetup->time / 1000);
        $dateTime->setTimeZone($timezone);

        return $dateTime;
    }

    protected function fetchPendingMeetups()
    {
        $url = self::API_BASE . 'events?';
        $params = array(
            'group_id' => self::ZGPHP_GROUP_ID,
            'key' => $this->apiKey,
            'status' => 'upcoming',
            'text_format' => 'plain',
            'fields' => 'timezone'
        );
        $url .= http_build_query($params);

        $data = file_get_contents($url);
        if ($data === false) {
            throw new \Exception("Failed loading meetup.com data.");
        }

        $data = json_decode($data);
        if ($data === false) {
            throw new \Exception("Failed decoding meetup.com data.");
        }

        return $data->results;
    }
}
