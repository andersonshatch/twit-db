<?php

chdir(dirname(__FILE__));
require_once("../lib/ConfigHelper.php");

ConfigHelper::requireConfig("../config.php");
$mysqli = ConfigHelper::getDatabaseConnection();

require_once('../lib/QueryHolder.php');
require_once('../lib/QueryUtils.php');
require_once('../lib/SearchQueryBuilder.php');
require_once('../lib/TweetFormatter.php');

header('Content-Type: application/json; charset=utf-8');

$results = [];

//if since_id is set, and max_id isn't, then get the "page" before since_id
$sortAscending = array_key_exists('since_id', $_GET) && !array_key_exists('max_id', $_GET);

if(array_key_exists("count-only", $_GET)) {
	list($queryString, $queryParams) = SearchQueryBuilder::buildQuery($_GET, true, $sortAscending);
	$rowCountQuery = QueryHolder::prepareAndHoldQuery($mysqli, $queryString);
	QueryUtils::bindQueryWithParams($rowCountQuery, $queryParams);
	$rowCountQuery->execute();
	$rowCountRow = $rowCountQuery->get_result()->fetch_array();
	$results["matchingTweets"] = number_format($rowCountRow[0]);
	$results["countQuery"] = $queryString;
	$results["queryParams"] = $queryParams;
} else {
	list($queryString, $queryParams) = SearchQueryBuilder::buildQuery($_GET, false, $sortAscending);
	$query = QueryHolder::prepareAndHoldQuery($mysqli, $queryString);
	QueryUtils::bindQueryWithParams($query, $queryParams);
	$query->execute();
	$tweets = $query->get_result()->fetch_all(MYSQLI_ASSOC);

	if($sortAscending) {
		//reverse so tweets are always served newest to oldest
		$tweets = array_reverse($tweets);
	}

	$tweets = TweetFormatter::formatTweets($tweets, !(array_key_exists('disable-linkification', $_GET) && $_GET['disable-linkification'] == "true") );

	$previousPage = null;
	$nextPage = null;

	if(($count = count($tweets)) > 0) {
		$firstTweet = $tweets[0];
		$previousPageParams = ["since_id" => $firstTweet->getId()];

		if ($firstTweet->getRelevance() !== null) {
			$previousPageParams["relevance"] = $firstTweet->getRelevance();
		}

		$previousPage = http_build_query(array_merge($_GET, $previousPageParams));

		$lastTweet = $tweets[$count - 1];
		$nextPageParams = ["max_id" => $lastTweet->getId()];

		if($lastTweet->getRelevance() !== null) {
			$nextPageParams["relevance"] = $lastTweet->getRelevance();
		}

		$nextPage = http_build_query(array_merge($_GET, $nextPageParams));
	}

	$results["tweets"] = $tweets;
	$results["debug"]["queryString"] = $queryString;
	$results["debug"]["queryParams"] = $queryParams;
	$results["nextPage"] = $nextPage;
	$results["previousPage"] = $previousPage;
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
