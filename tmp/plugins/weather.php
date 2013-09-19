<?php

/*
 * Fetches weather for a given city from openweathermap.org and displays it.
 */

$client->on('irc.received', function($message, $write, $connection, $logger) {
    if (isset($message['command']) && $message['command'] == 'PRIVMSG') {
        $channel = $message['params']['receivers'];
        $text = $message['params']['text'];

        if (preg_match('/^weather(.+)$/', $text, $matches)) {
            $logger->debug("Fetching weather for: {$matches[1]}");
            $weather = get_weather($matches[1]);
            $write->ircPrivmsg($channel, $weather);
        }
    }
});

function get_weather($query)
{
    $query = urlencode($query);
    $url = "http://api.openweathermap.org/data/2.5/weather?q=$query&units=metric";

    $data = file_get_contents($url);
    if ($data === false) {
        return "Failed loading data from api.openweathermap.org";
    }

    $data = json_decode($data);
    if ($data === false) {
        return "Failed decoding data from api.openweathermap.org";
    }

    $weather = array();
    foreach($data->weather as $item) {
        $weather[] = $item->description;
    }
    $weather = implode(', ', $weather);

    $temp = $data->main->temp . "°C";
    $pressure = $data->main->pressure . " hPa";
    $humidity = "humidity {$data->main->humidity}%";
    $wind = wind_to_str($data->wind);

    $message = "Weather in $data->name: $weather, $temp, $pressure, $wind, $humidity";

    return $message;
}

// wind.speed - Wind speed in mps, mandatory
// wind.deg   - Wind direction in degrees, mandatory
function wind_to_str($wind)
{
    if ($wind->deg < 0 || $wind->deg > 360) {
        return "ERROR: invalid wind direction (deg=$wind->deg)";
    }

    // Map degree to geographic direction
    $dirMap = [
        22.5 => 'northerly',
        67.5 => 'northeasterly',
        112.5 => 'easterly',
        157.5 => 'southeasterly',
        202.5 => 'southerly',
        247.5 => 'southwesterly',
        292.5 => 'westerly',
        337.5 => 'northwesterly',
        360 => 'northerly',
    ];

    foreach($dirMap as $degLimit => $direction) {
        if ($wind->deg < $degLimit) {
            break;
        }
    }

    return "wind {$wind->speed}mph $direction";
}
