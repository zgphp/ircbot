<?php

namespace ZgPhp\IrcBot\Plugins;

/**
 * Weather plugin
 *
 * Fetches and writes weather data from api.openweathermap.org
 *
 * Trigger:
 * ```
 * weather <query>
 * ```
 * where query is the desired location.
 *
 * For example:
 * ```
 * weather zagreb
 * ```
 *
 * will produce something like:
 * ```
 *
 * ```
 */
class Weather extends MessagePatternPlugin
{
    protected $pattern = '/^weather (.+)$/';

    protected function handle($message, $matches, $write)
    {
        $channel = $message['params']['receivers'];
        $query = $matches[1];

        try {
            $weather = $this->getWeather($query);
            $write->ircPrivmsg($channel, $weather);
        } catch (\Exception $ex) {
            $write->ircPrivmsg($channel, "Weather plugin error: " . $ex->getMessage());
        }
    }

    /** Returns weather data for given query. */
    protected function getWeather($query)
    {
        $data = $this->fetchWeatherData($query);

        $weather = array();
        foreach($data->weather as $item) {
            $weather[] = $item->description;
        }
        $weather = implode(', ', $weather);

        $temp = $data->main->temp . "°C";
        $pressure = $data->main->pressure . " hPa";
        $humidity = "humidity {$data->main->humidity}%";
        $wind = $this->parseWind($data->wind);

        $message = "Weather in $data->name: $weather, $temp, $pressure, $wind, $humidity";

        return $message;
    }

    /** Fetches data from server */
    protected function fetchWeatherData($query)
    {
        $query = urlencode($query);
        $url = "http://api.openweathermap.org/data/2.5/weather?q=$query&units=metric";

        $data = file_get_contents($url);
        if ($data === false) {
            throw new \Exception("Failed loading data from api.openweathermap.org");
        }

        $data = json_decode($data);
        if ($data === false) {
            throw new \Exception ("Failed decoding data from api.openweathermap.org");
        }

        return $data;
    }

    /**
     * Parses wind data into english.
     *
     * wind.speed - Wind speed in mps, mandatory
     * wind.deg   - Wind direction in degrees, mandatory
     */
    protected function parseWind($wind)
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
}
