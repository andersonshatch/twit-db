<?php

require_once('../config.php');
require_once('../lib/buildQuery.php');
require_once('../lib/linkify_tweet.php');

$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
$mysqli->set_charset("utf8");

header('Content-Type: application/json; charset=utf-8');

$results = array();

if(array_key_exists("count-only", $_GET)) {
	$rowCountQuery = $mysqli->query($countQuery = buildQuery($_GET, $mysqli, true));
	$rowCountRow = $rowCountQuery->fetch_row();
	$results["matchingTweets"] = number_format($rowCountRow[0]);
	$results["countQuery"] = $countQuery;
} else {
	$query = $mysqli->query($queryString = buildQuery($_GET, $mysqli));
	$tweets = $query->fetch_all(MYSQLI_ASSOC);

	foreach($tweets as &$tweet) {
		$createdAt = date_create_from_format('Y-m-d H:i:s', $tweet['created_at']);
		$tweet['datetime'] = $createdAt->format('c');
		$tweet['timestamp_title'] = $createdAt->format('G:i M jS \'y');
		$tweet['text'] = linkify_tweet($tweet['text'], $tweet['entities_json']);
	}

	$results["tweets"] = $tweets;
	$results["queryString"] = $queryString;
}

$output = json_encode($results);
ob_start('ob_gzhandler');

if(array_key_exists('callback', $_GET) && $_GET['callback'] != '') {
	echo "{$_GET['callback']}($output)"; 
} else {
	echo $output;
}

exit;

?>
