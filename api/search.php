<?php

require_once("../lib/ConfigHelper.php");

ConfigHelper::requireConfig("../config.php");
$mysqli = ConfigHelper::getDatabaseConnection();

require_once('../lib/buildQuery.php');
require_once('../lib/linkify_tweet.php');

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

	$nextPage = null;

	if($count = count($tweets) > 0) {
		$lastTweet = $tweets[count($tweets) - 1];
		$params = array("max_id" => $lastTweet["id"]);

		if(array_key_exists('relevance', $tweet)) {
			$params["relevance"] = $lastTweet["relevance"];
		}

		$nextPage = http_build_query(array_merge($_GET, $params));
	}

	$results["tweets"] = $tweets;
	$results["queryString"] = $queryString;
	$results["nextPage"] = $nextPage;
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
