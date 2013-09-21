<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Event;
use ZgPhp\IrcBot\MessagePatternPlugin;

/**
 * Displays meetup data from meetup.com.
 *
 * Required settings:
 *
 * meetup:
 *     api_key:  # meetup.com api key
 *     group_id: # the ID of the group to work on
 *
 * Get the API key from:
 * http://www.meetup.com/meetup_api/key/
 */
class Meetup extends MessagePatternPlugin
{
    protected $pattern = '/^!meetup(.*)$/i';

    const API_BASE = "http://api.meetup.com/2/";

    /** The meetup.com api access key, read from settings (meetup.api_key).*/
    private $apiKey;

    /** The meetup.com group ID for which to fetch data. */
    private $groupID;

    protected function init()
    {
        $this->apiKey = $this->getSetting(array('meetup', 'api_key'));
        $this->groupID = $this->getSetting(array('meetup', 'group_id'));
    }

    protected function handle(Event $event, $matches)
    {
        $command = isset($matches[1]) ? $matches[1] : null;
        $command = strtolower(trim($command));

        switch($command) {
            case "next":
                $this->handleNext($event);
                break;
            case "upcoming":
                $this->handleUpcoming($event);
                break;
            default:
                $this->showUsage($event);
        }
    }

    protected function showUsage(Event $event)
    {
        $event->reply("Meetup plugin usage:");
        $event->reply("    !meetup next - show details for next meetup");
        $event->reply("    !meetup upcoming - show list of upcoming meetups");
    }

    /** Triggered on "next" command. */
    protected function handleNext(Event $event)
    {
        $meetups = $this->fetchPendingMeetups();
        if (empty($meetups)) {
            $event->reply("Next meetup not scheduled.");
            return;
        }

        // Meetups are sorted by time, take first one
        $meetup = $meetups[0];

        if ($meetup->visibility !== 'public') {
            $event->reply("Next meetup not scheduled.");
            return;
        }

        $text = $this->parseMeetup($meetup);
        $event->reply($text);
    }

    /** Triggered on "upcoming" command. */
    protected function handleUpcoming(Event $event)
    {
        $meetups = $this->fetchPendingMeetups();
        if (empty($meetups)) {
            $event->reply("No meetups are scheduled.");
            return;
        }

        $event->reply("Upcoming meetups:");
        foreach($meetups as $meetup) {
            if ($meetup->visibility == 'public') {
                $text = $this->parseMeetupShort($meetup);
                $event->reply($text);
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

    /** Parses meetup data to a string (shorter). */
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

    /** Loads pending meetups via meetup.com API. */
    protected function fetchPendingMeetups()
    {
        $url = self::API_BASE . 'events?';
        $params = array(
            'group_id' => $this->groupID,
            'key' => $this->apiKey,
            'status' => 'upcoming',
            'text_format' => 'plain',
            'fields' => 'timezone',
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
