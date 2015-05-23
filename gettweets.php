<?php

if(in_array('--quiet', $argv))
	define('QUIET', true);

$GLOBALS['totalTweetsAdded'] = 0;
$GLOBALS['responseTweetsAdded'] = 0;
$GLOBALS['requestCount'] = 0;

chdir(dirname(__FILE__));
require_once("lib/ConfigHelper.php");
ConfigHelper::requireConfig("config.php");
$mysqli = ConfigHelper::getDatabaseConnection(true);
$timelines = Timeline::all($mysqli);
$twitterObj = ConfigHelper::getTwitterObject();

foreach($timelines as $timeline) {
	$tweetsAdded = $GLOBALS['totalTweetsAdded'];
	getTimelineAndStore($twitterObj, $mysqli, $timeline);
	$tweetsAdded = $GLOBALS['totalTweetsAdded'] - $tweetsAdded;
	output("Added $tweetsAdded ".getSingularOrPlural("tweet", $tweetsAdded)." from {$timeline->getName()}\n");
}

$mysqli->close();
output("In total added {$GLOBALS['totalTweetsAdded']} ".getSingularOrPlural("tweet", $GLOBALS['totalTweetsAdded']).".\n");
output("Used {$GLOBALS['requestCount']} ".getSingularOrPlural("request", $GLOBALS['requestCount']).".\n");

function getTimelineAndStore(EpiTwitter $twitterObj, mysqli $mysqli, Timeline $timeline) {
	require_once 'lib/storestatusesandusers.php';
	$failCount = 0;
	$maxRetries = 10;
	$tweetsAdded = 0;
	while(true) {
		try {
			$statuses = $twitterObj->get($timeline->getRequestEndpoint(), $timeline->getRequestParameters());
			$GLOBALS['requestCount']++;
			$maxId = null;
			foreach($statuses as $tweet) {
				storeTweet($tweet, $mysqli);
				if($tweet->id > $timeline->getLastSeenId()) {
					$timeline->setLastSeenId($tweet->id);
					$timeline->setLastUpdatedAt(new DateTime());
					$timeline->save();
				}
				$maxID = $tweet->id - 1;
				$tweetsAdded++;
			}
			$statuses = null;	//free up some memory
			if($tweetsAdded > 0) {
				//added some tweets, check API again to see if there are still more (between since_id and max_id)
				$timeline->setMaxId($maxID);
				getTimelineAndStore($twitterObj, $mysqli, $timeline);
			}
			break;
		} catch(EpiTwitterNotFoundException $e) {
			//TODO: catch specific exceptions here. Especially rate limited.
			if($failCount++ > 2) {
				echo "ERROR: Twitter reports {$timeline->getName()} could not be found. Remove this from config if it no longer exists.\n";
				return;
			}
		} catch(EpiTwitterException $e) {
			if($failCount++ == $maxRetries) {
				echo "FAILED getting tweets for {$timeline->getName()}, $failCount times. Moving on.\n";
				return;
			}
			output("WARNING: got ".get_class($e)." whilst updating {$timeline->getName()}. ({$e->getMessage()})\n");
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
