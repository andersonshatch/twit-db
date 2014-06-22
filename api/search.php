<?php

chdir(dirname(__FILE__));
require_once("../lib/ConfigHelper.php");

ConfigHelper::requireConfig("../config.php");
$mysqli = ConfigHelper::getDatabaseConnection();

require_once('../lib/buildQuery.php');
require_once('../lib/TweetFormatter.php');

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

	TweetFormatter::formatTweets($tweets, !(array_key_exists('disable-linkification', $_GET) && $_GET['disable-linkification'] == "true") );

	$nextPage = null;

	if(($count = count($tweets)) > 0) {
		$lastTweet = $tweets[$count - 1];
		$params = array("max_id" => $lastTweet["id"]);

		if(array_key_exists('relevance', $lastTweet)) {
			$params["relevance"] = $lastTweet["relevance"];
		}

		$nextPage = http_build_query(array_merge($_GET, $params));
	}

	$results["tweets"] = $tweets;
	$results["queryString"] = $queryString;
	$results["nextPage"] = $nextPage;
}

$mysqli->close();

$output = json_encode($results);

if(array_key_exists('callback', $_GET) && $_GET['callback'] != '') {
	echo "{$_GET['callback']}($output)"; 
} else {
	echo $output;
}

exit;

?>
