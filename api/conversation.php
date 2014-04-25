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
$results = $conversation->getConversation();

TweetFormatter::formatTweets($results, !(array_key_exists('disable-linkification', $_GET) && $_GET['disable-linkification'] == "true") );

ob_start('ob_gzhandler');

echo json_encode(array("tweets" => $results, "queries" => $conversation->getQueries()));

?>
