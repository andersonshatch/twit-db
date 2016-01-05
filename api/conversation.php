<?php

chdir(dirname(__FILE__));
require_once('../lib/ConfigHelper.php');
require_once('../lib/Conversation.php');
require_once('../lib/TweetFormatter.php');

ConfigHelper::requireConfig('../config.php');

header('Content-Type: application/json; charset=utf-8');

if(!array_key_exists('id', $_GET)) {
	header('HTTP/1.1 400 Bad Request');
	echo json_encode(array());
	exit;
}

$conversation = new Conversation($_GET['id']);
$tweets = $conversation->getConversation();

$mysqli = ConfigHelper::getDatabaseConnection();

$tweets = TweetFormatter::formatTweets($tweets, !(array_key_exists('disable-linkification', $_GET) && $_GET['disable-linkification'] == "true"), $mysqli);

$mysqli->close();

$results = [
	"tweets"    => $tweets,
	"debug"     => ["queries" => $conversation->getQueries()]
];

echo json_encode($results);

?>
