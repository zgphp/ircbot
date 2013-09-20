<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\MessagePatternPlugin;

/**
 * ZgPHP meetup data from meetup.com.
 */
class Meetup extends MessagePatternPlugin
{
    protected $pattern = '/^!meetup (.+)$/i';

    const API_BASE = "http://api.meetup.com/2/";

    const ZGPHP_GROUP_ID = 8328272;


    private $apiKey;
    //events?key=427204e61372b4ee4c553d3b30933&group_id=8328272&status=upcoming

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
        }
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

        print_r($meetup);

        if ($meetup->visibility !== 'public') {
            $write->ircPrivmsg($channel, "Next meetup not scheduled.");
            return;
        }

        $venue = [];
        $venue[] = $meetup->venue->name;
        if (isset($meetup->venue->address_1)) {
            $venue[] = $meetup->venue->address_1;
        }
        if (isset($meetup->venue->address_2)) {
            $venue[] = $meetup->venue->address_2;
        }
        if (isset($meetup->venue->address_3)) {
            $venue[] = $meetup->venue->address_3;
        }
        $venue = implode(', ', $venue);

        $time = date('d.m.Y. \\a\\t H:i', $meetup->time);
        $url = $meetup->event_url;
        $attending = $meetup->yes_rsvp_count;
        $text = "Next ZgPHP meetup is scheduled for $time at $venue. Attending: $attending developers. RSVP here: $url";

        $channel = $message['params']['receivers'];
        $write->ircPrivmsg($channel, $text);
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

/*
stdClass Object
(
    [results] => Array
        (
            [0] => stdClass Object
                (
                    [status] => upcoming
                    [visibility] => public
                    [maybe_rsvp_count] => 0
                    [venue] => stdClass Object
                        (
                            [id] => 13213372
                            [lon] => 15.974297
                            [repinned] =>
                            [name] => Mama
                            [address_1] => Preradoviceva 18
                            [lat] => 45.810158
                            [country] => hr
                            [city] => Zagreb
                        )

                    [id] => 140394332
                    [utc_offset] => 7200000
                    [time] => 1382023800000
                    [waitlist_count] => 0
                    [announced] =>
                    [updated] => 1379280288000
                    [yes_rsvp_count] => 3
                    [created] => 1379280288000
                    [event_url] => http://www.meetup.com/ZgPHP-meetup/events/140394332/
                    [description] => <br />
                    [name] => ZgPHP Meetup #26
                    [headcount] => 0
                    [group] => stdClass Object
                        (
                            [id] => 8328272
                            [group_lat] => 45.799999237061
                            [name] => ZgPHP meetup
                            [group_lon] => 15.970000267029
                            [join_mode] => open
                            [urlname] => ZgPHP-meetup
                            [who] => Developers
                        )

                )

        )

    [meta] => stdClass Object
        (
            [lon] =>
            [count] => 1
            [link] => http://api.meetup.com/2/events
            [next] =>
            [total_count] => 1
            [url] => http://api.meetup.com/2/events?key=427204e61372b4ee4c553d3b30933&group_id=8328272&status=upcoming&order=time&limited_events=False&desc=false&offset=0&format=json&page=200&fields=
            [id] =>
            [title] => Meetup Events v2
            [updated] => 1379280288000
            [description] => Access Meetup events using a group, member, or event id. Events in private groups are available only to authenticated members of those groups. To search events by topic or location, see [Open Events](/meetup_api/docs/2/open_events).
            [method] => Events
            [lat] =>
        )

)
*/