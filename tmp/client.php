<?php

require "vendor/autoload.php";

use Phergie\Irc\Client\React\Client;
use Phergie\Irc\Connection;

$connection = new Connection();
$connection->setServerHostname('chat.freenode.net');
$connection->setServerPort(6666);
$connection->setNickname('zgphpbot');
$connection->setUsername('zgphpbot');
// $connection->setPassword('m4m4m14m14');
$connection->setRealname('A test bot');

// $connection->setHostname('hostname');
// $connection->setServername('servername');

$settings = [
    "autojoin" => ["##test"]
];

$client = new Client();
$logger = $client->getLogger();

include 'plugins/ping.php';
include 'plugins/autojoin.php';
include 'plugins/weather.php';

$client->run($connection);

/*
2013-09-18 16:00:24 DEBUG :ihabunek!~ihabunek@194.152.205.71 PRIVMSG mytestbot :ho
array(7) {
  ["prefix"]=>
  string(34) ":ihabunek!~ihabunek@194.152.205.71"
  ["nick"]=>
  string(8) "ihabunek"
  ["user"]=>
  string(24) "~ihabunek@194.152.205.71"
  ["command"]=>
  string(7) "PRIVMSG"
  ["params"]=>
  array(3) {
    ["all"]=>
    string(13) "mytestbot :ho"
    ["receivers"]=>
    string(9) "mytestbot"
    ["text"]=>
    string(2) "ho"
  }
  ["message"]=>
  string(58) ":ihabunek!~ihabunek@194.152.205.71 PRIVMSG mytestbot :ho
"
  ["targets"]=>
  array(1) {
    [0]=>
    string(9) "mytestbot"
  }
}
*/

/*

2013-09-18 15:58:51 DEBUG :ihabunek!~ihabunek@194.152.205.71 PRIVMSG ##test :hi
array(7) {
  ["prefix"]=>
  string(34) ":ihabunek!~ihabunek@194.152.205.71"
  ["nick"]=>
  string(8) "ihabunek"
  ["user"]=>
  string(24) "~ihabunek@194.152.205.71"
  ["command"]=>
  string(7) "PRIVMSG"
  ["params"]=>
  array(3) {
    ["all"]=>
    string(10) "##test :hi"
    ["receivers"]=>
    string(6) "##test"
    ["text"]=>
    string(2) "hi"
  }
  ["message"]=>
  string(55) ":ihabunek!~ihabunek@194.152.205.71 PRIVMSG ##test :hi
"
  ["targets"]=>
  array(1) {
    [0]=>
    string(6) "##test"
  }
}
*/