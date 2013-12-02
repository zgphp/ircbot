<?php

namespace ZgPhp\IrcBot\Plugins;

use ZgPhp\IrcBot\Plugin;
use ZgPhp\IrcBot\Event;

/**
 * Plugin which pings web sites to make sure they're up.
 *
 * Looks for tweets matching a search query and posts them to the channel.
 *
 * Settings:
 * ```
 * webchecker:
 *     urls:
 *         - http://www.zgphp.org/
 *         - http://2013.zgphp.org/
 * ```
 */
class WebChecker extends Plugin
{
    const DEFAULT_CHECK_DELAY = 60;

    const DEFAULT_RESEND_DELAY = 1200;

    /** Use google's public DNS server for resolving hostnames. */
    const DNS_SERVER = "8.8.8.8";

    /** Array of URLs to check. */
    private $urls;

    /** How often to check site availability (seconds). */
    private $checkDelay;

    /** How often to re-send notification for unavailable sites. */
    private $resendDelay;

    /** Array of URLs which are currently down. */
    private $downUrls;

    /**
     * The write stream.
     * @var Phergie\Irc\Client\React\WriteStream
     */
    private $write;

    /**
     * The HTTP client used for checking web site availability.
     * @var React\HttpClient\Client
     */
    private $httpClient;

    protected function init()
    {
        $this->channel = $this->getSetting(array('webchecker', 'channel'));
        $this->checkDelay = $this->getSetting(array('webchecker', 'check_delay'), true, self::DEFAULT_CHECK_DELAY);
        $this->resendDelay = $this->getSetting(array('webchecker', 'resend_delay'), true, self::DEFAULT_RESEND_DELAY);
        $this->urls = $this->getSetting(array('webchecker', 'urls'));

        $loop = $this->getClient()->getLoop();

        // Setup DNS resolver
        $dnsResolverFactory = new \React\Dns\Resolver\Factory();
        $dnsResolver = $dnsResolverFactory->createCached(self::DNS_SERVER, $loop);

        // Setup HTTP client
        $factory = new \React\HttpClient\Factory();
        $this->httpClient = $factory->create($loop, $dnsResolver);
    }

    /** Activate after MOTD has been recieved. */
    public function onEndMotd(Event $event)
    {
        // Save the write stream so the plugin can write on demand.
        // Not the most elegant solution, but best I could come up with.
        $this->write = $event->write;

        $this->log->debug("WebChecker plugin: setting up periodic timer with delay of $this->checkDelay seconds.\n");
        $loop = $this->getClient()->getLoop();
        $loop->addPeriodicTimer($this->checkDelay, array($this, 'checkUrls'));
    }

    public function checkUrls()
    {
        foreach($this->urls as $url) {
            // HEAD request means no data is returned, only headers
            $this->log->debug("Requesting HEAD $url\n");
            $request = $this->httpClient->request('HEAD', $url);

            $request->on('response', function ($response) use ($url) {
                $code = $response->getCode();
                $phrase = $response->getReasonPhrase();
                $this->log->debug("Server responded $code - $phrase\n");

                if ($code < 200 || $code >= 400) {
                    $this->down($url, "Server responded $code - $phrase");
                } else {
                    $this->up($url);
                }
            });

            $request->on('error', function ($ex) use ($url) {
                $errors = array();
                $errors[] = "Exception: " . $ex->getMessage();
                while($ex = $ex->getPrevious()) {
                    $errors[] = "Caused by: " . $ex->getMessage();
                }
                $this->down($url, implode("; ", $errors));
            });

            $request->end();
        }
    }

    /** Mark URL as being down. */
    public function down($url, $msg)
    {
        if (!isset($this->downUrls[$url])) {
            // The url not marked as down, notify and save time
            $this->notify("$url is down. Reason: $msg");
            $this->downUrls[$url] = time();
        } else {
            // The url already marked as down
            $lastNotify = $this->downUrls[$url];
            if (time() - $lastNotify > $this->resendDelay) {
                $this->notify("$url is still down. Reason: $msg");
                $this->downUrls[$url] = time();
            }
        }
    }

    /** Mark URL as being up. */
    public function up($url)
    {
        if (isset($this->downUrls[$url])) {
            $this->notify("$url has recovered.");
            unset($this->downUrls[$url]);
        }
    }

    private function notify($text)
    {
        $this->write->ircPrivMsg($this->channel, $text);
    }
}
