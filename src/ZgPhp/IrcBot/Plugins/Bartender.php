<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Event;
use ZgPhp\IrcBot\MessagePatternPlugin;

/**
 * Bartender plugin
 *
 * Handles request for beverages.
 *
 * No settings.
 */
class Bartender extends MessagePatternPlugin
{
    protected $pattern = '/^!(beer|coffee)(.*)$/i';

    // The beer "database"
    private $beers = array(
        "Favorit",
        "Kaj",
        "Karlovacko Crno",
        "Karlovacko",
        "Kasacko",
        "Ozujsko",
        "Crni Pan",
        "Psenicni Pan",
        "Zlatni Pan",
        "Pan",
        "Starocesko Crveno Premium",
        "Starocesko Zimsko",
        "Starocesko",
        "Svijetlo Velebitsko",
        "Tamno Velebitsko",
        "Vukovarsko",
    );

    protected function handle(Event $event, $matches)
    {
        $command = trim($matches[1]);
        $who = trim($matches[2]);

        if (empty($who)) {
            $event->reply("Who should I throw it to?");
            return;
        }

        if ($who == "me") {
            $who = $event->message['nick'];
        }

        switch ($command) {
            case "beer":
                $this->handleBeer($event, $who);
                break;
            case "coffee":
                $this->handleCoffee($event, $who);
                break;
        }
    }

    private function handleBeer(Event $event, $who)
    {
        $beer = $this->getRandomBeer();
        $text = "throws $who a cool bottle of $beer";

        $target = $event->getSource();
        $event->write->ctcpAction($target, $text);
    }

    private function handleCoffee(Event $event, $who)
    {
        $text = "throws $who a hot cup of coffee";

        $target = $event->getSource();
        $event->write->ctcpAction($target, $text);
    }

    private function getRandomBeer()
    {
        $max = count($this->beers) - 1;
        $key = rand(0, $max);
        return $this->beers[$key];
    }
}
