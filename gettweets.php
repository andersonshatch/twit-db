<?php

if(in_array('--quiet', $argv))
	define('QUIET', true);

chdir(dirname(__FILE__));
require_once("lib/ConfigHelper.php");
ConfigHelper::requireConfig("config.php");
$mysqli = ConfigHelper::getDatabaseConnection(true);
$timelines = Timeline::all($mysqli);
$twitterObj = ConfigHelper::getTwitterObject();

$stats = Stats::instance();

foreach($timelines as $timeline) {
	$tweetsAddedBefore = $stats->totalTweetsAdded;
	getTimelineAndStore($twitterObj, $mysqli, $timeline);
	$tweetsAdded = $stats->totalTweetsAdded - $tweetsAddedBefore;
	output("Added $tweetsAdded ".getSingularOrPlural("tweet", $tweetsAdded)." from {$timeline->getName()}\n");
}

output("In total added $stats->totalTweetsAdded ".getSingularOrPlural("tweet", $stats->totalTweetsAdded).".\n");
output("Used $stats->requestCount ".getSingularOrPlural("request", $stats->requestCount).".\n");

function getTimelineAndStore(EpiTwitter $twitterObj, mysqli $mysqli, Timeline $timeline) {
	require_once 'lib/TweetStorer.php';
	require_once 'lib/FavoriteUtil.php';
	$failCount = 0;
	$maxRetries = 10;
	$tweetsAdded = 0;

	$stats = Stats::instance();

	$favoriteTimeline = $timeline->getTimelineType() == TimelineType::FavoriteTimeline;

	while(true) {
		try {
			$statuses = $twitterObj->get($timeline->getRequestEndpoint(), $timeline->getRequestParameters());
			$stats->requestCount++;
			$maxId = null;
			if($timeline->getTimelineType() == TimelineType::SearchTimeline) {
				$statuses = $statuses["statuses"];
			}
			foreach($statuses as $tweet) {
				$tweet = (object)$tweet;
				$tweet->user = (object)$tweet->user;
				TweetStorer::storeTweet($tweet, $mysqli);
				if($favoriteTimeline) {
					FavoriteUtil::storeFavorite($tweet, $mysqli);
				}
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
			output("WARNING: got " . get_class($e) . " whilst updating {$timeline->getName()}. ({$e->getMessage()})\n");
			sleep(2 * $failCount - 1);
		}
	}
	$stats->totalTweetsAdded += $tweetsAdded;
}


function getSingularOrPlural($string, $number) {
	if($number == 1) {
		return $string;
	} else {
		return $string."s";
	}
}

function output($text) {
    if(!defined('QUIET') || QUIET === false) {
	    echo $text;
    }
}

class Stats {
	private static $instance;

	private function __construct() {
	}

	public $totalTweetsAdded = 0;
	public $requestCount = 0;

	public static function instance() {
		if(self::$instance === null) {
			self::$instance = new Stats();
		}

		return self::$instance;
	}
}

?>
