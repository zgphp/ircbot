<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Event;
use ZgPhp\IrcBot\MessagePatternPlugin;

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
 * Weather for Zagreb, HR
 * Sky is Clear
 * Temperature: 18°C
 * Atmospheric pressure: 1017 hPa
 * Humidity: 55%
 * Wind: 2.1mph southerly
 * ```
 */
class Weather extends MessagePatternPlugin
{
    protected $pattern = '/^!weather(.*)$/';

    protected function handle(Event $event, $matches)
    {
        $query = trim($matches[1]);

        if (empty($query)) {
            $this->showUsage($event);
        }

        try {
            $messages = $this->getWeather($query);
            foreach($messages as $message) {
                $event->reply($message);
            }
        } catch (\Exception $ex) {
            $event->reply("Error: " . $ex->getMessage());
        }
    }

    protected function showUsage(Event $event)
    {
        $event->reply("Weather plugin usage:");
        $event->reply("!weather <place> - show weather for given place");
    }

    /** Returns weather data for given query. */
    protected function getWeather($query)
    {
        $data = $this->fetchWeatherData($query);

        $weather = array();
        foreach($data->weather as $item) {
            $weather[] = $item->description;
        }
        $weather = ucfirst(implode(', ', $weather));

        $messages = array();
        $messages[] = "Weather for {$data->name}, {$data->sys->country}";
        $messages[] = $weather;
        $messages[] = "Temperature: {$data->main->temp}°C";
        $messages[] = "Atmospheric pressure: {$data->main->pressure} hPa";
        $messages[] = "Humidity: {$data->main->humidity}%";
        $messages[] = "Wind: " . $this->parseWind($data->wind);

        return $messages;
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

        if ($data->cod == 404) {
            throw new \Exception("Place \"$query\" not found");
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
        $dirMap = array(
            22.5 => 'northerly',
            67.5 => 'northeasterly',
            112.5 => 'easterly',
            157.5 => 'southeasterly',
            202.5 => 'southerly',
            247.5 => 'southwesterly',
            292.5 => 'westerly',
            337.5 => 'northwesterly',
            360 => 'northerly',
        );

        foreach($dirMap as $degLimit => $direction) {
            if ($wind->deg < $degLimit) {
                break;
            }
        }

        return "{$wind->speed}mph $direction";
    }
}
