<?php

if(in_array('--quiet', $argv))
	define('QUIET', true);

$GLOBALS['totalTweetsAdded'] = 0;
$GLOBALS['responseTweetsAdded'] = 0;
$GLOBALS['requestCount'] = 0;

chdir(dirname(__FILE__));
if(is_readable('config.php'))
	require_once 'config.php';
else
	die("ERROR: Config file (config.php) doesn't exist. Visit setup.php in a browser to create it.\n");
require_once 'twitter-async/EpiCurl.php';
require_once 'twitter-async/EpiOAuth.php';
require_once 'twitter-async/EpiTwitter.php';

$mysqli = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if($mysqli->connect_error)
    die("Could not connect to MySQL. {$mysqli->connect_error}\n");
$mysqli->set_charset("utf8");
$twitterObj = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_USER_TOKEN, TWITTER_USER_SECRET);
$twitterObj->useApiVersion(1.1);

require_once 'lib/additional_users.php';
$additionalUsers = create_users_array(ADDITIONAL_USERS);

$homeEndpoint = "/statuses/home_timeline.json";
$mentionsEndpoint = "/statuses/mentions.json";
$userEndpoint = "/statuses/user_timeline.json";

$timelineParams = array("count" => 120, "include_rts" => "true", "page" => 1, "include_entities" => "true");

$requests[] = array("endpoint" => $homeEndpoint, "tableName" => "home", "params" => $timelineParams);
if(defined("MENTIONS_TIMELINE") && MENTIONS_TIMELINE == "true") {
	$requests[] = array("endpoint" => $mentionsEndpoint, "tableName" => "mentions", "params" => $timelineParams);
}
foreach($additionalUsers as $user) {
	$userTimelineParam = $timelineParams;
	$userTimelineParam['screen_name'] = $user;
	$tableName = "@".$user;
	$requests[] = array("endpoint" => $userEndpoint, "tableName" => $tableName, "params" => $userTimelineParam);	
}

foreach($requests as $request) {
	$lastIDQueryString = "SELECT id FROM `{$request['tableName']}` ORDER BY id DESC LIMIT 1";
	$lastIDQuery = $mysqli->query($lastIDQueryString);
	if(!$lastIDQuery) {
		require_once 'lib/createtables.php';
		output("Creating table {$request['tableName']}\n");
		if(!createTimelineTable($mysqli, $request['tableName'])) {
			echo "ERROR: Couldn't create table {$request['tableName']}\n";
			continue; //go to next request
		}
		//table created successfully, run query again (almost definitely empty, unnecessary?)
		$lastIDQuery = $mysqli->query($lastIDQueryString);
	}
	$lastIDResult = $lastIDQuery->fetch_row();
	$request['params']['since_id'] = ($lastIDResult == '') ? 1 : $lastIDResult[0];
	$tweetsAdded = $GLOBALS['totalTweetsAdded'];
	getTimelineAndStore($twitterObj, $mysqli, $request);
	$tweetsAdded = $GLOBALS['totalTweetsAdded'] - $tweetsAdded;
	output("Added $tweetsAdded ".getSingularOrPlural("tweet", $tweetsAdded)." to {$request['tableName']}.\n");
}

$mysqli->close();
output("In total added {$GLOBALS['totalTweetsAdded']} ".getSingularOrPlural("tweet", $GLOBALS['totalTweetsAdded']).".\n");
output("Used {$GLOBALS['requestCount']} ".getSingularOrPlural("request", $GLOBALS['requestCount']).".\n");

function getTimelineAndStore($twitterObj, $mysqli, $requestObj) {
	require_once 'lib/storestatusesandusers.php';
	$failCount = 0;
	$maxRetries = 10;
	$tweetsAdded = 0;
	while(true) {
		try {
			$timeline = $twitterObj->get($requestObj['endpoint'], $requestObj['params']);
			$GLOBALS['requestCount']++;
			foreach($timeline as $tweet) {
				storeTweet($tweet, $requestObj['tableName'], $mysqli);
				$maxID = $tweet->id - 1;
				$tweetsAdded++;
			}
			$timeline = null;	//free up some memory
			if($tweetsAdded > 0) {
				//added some tweets, check API again to see if there are still more (between since_id and max_id)	
				$requestObj['params']['max_id'] = $maxID;
				getTimelineAndStore($twitterObj, $mysqli, $requestObj);
			}
			break;
		} catch(EpiTwitterNotFoundException $e) {
			//TODO: catch specific exceptions here. Especially rate limited.
			if($failCount++ > 2) {
				echo "ERROR: Twitter reports {$requestObj['tableName']} could not be found. Remove this username from config if it no longer exists.\n";
				return;
			}
		} catch(EpiTwitterException $e) {
			if($failCount++ == $maxRetries) {
				echo "FAILED getting tweets for {$requestObj['tableName']}, $failCount times. Moving on.\n";
				return;
			}
			output("WARNING: got ".get_class($e)." whilst updating {$requestObj['tableName']}.\n");
			sleep(2 * $failCount - 1);
		}
	}
	$GLOBALS['totalTweetsAdded'] += $tweetsAdded;
}

function getSingularOrPlural($string, $number) {
	if($number == 1)
		return $string;
	else
		return $string."s";
}

function output($text) {
    if(!defined('QUIET') || QUIET === false)
		echo $text;	
}

?>
