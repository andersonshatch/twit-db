<?php

require_once dirname(__FILE__).'/QueryHolder.php';
require_once dirname(__FILE__).'/Schema.php';

class ConfigHelper {

	private static $mysqli;

	/**
	 * Require the config file; will exit upon failure
	 * @param string $configPath path to the config file -- if relative, current directory is determined by the callee
	 */
	public static function requireConfig($configPath) {
		if(is_readable($configPath)) {
			require_once $configPath;
		} else if (!file_exists($configPath)) {
			self::exitWithError("Config file (config.php) doesn't exist. Visit setup.php in a browser to create it.");
		} else {
			self::exitWithError("Config file (config.php) is not readable.");
		}
	}

	/**
	 * Get a configured database connection; will exit upon failure
	 * @param boolean $allowSetup whether to setup tables -- only enable when execution will not timeout
	 * @return mysqli $mysqli database handle
	 */
	public static function getDatabaseConnection($allowSetup = false) {
		if(self::$mysqli !== null) {
			return self::$mysqli;
		}

		if(!extension_loaded("mysqli")) {
			self::exitWithError("Mysqli extension not loaded. Cannot proceed.");
		}

		$mysqli = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

		if($mysqli->connect_error) {
			self::exitWithError("Could not connect to MySQL. {$mysqli->connect_error}");
		}

		if(!@$mysqli->set_charset("utf8mb4") || $mysqli->error) {
			self::exitWithError("Could not select utf8mb4 charset. {$mysqli->error}. See https://github.com/andersonshatch/twit-db/issues/7");
		}

		if($allowSetup) {
			Schema::setupTables($mysqli);
		}

		self::$mysqli = $mysqli;
		register_shutdown_function('ConfigHelper::shutdown');

		return $mysqli;
	}

	/**
	 * Prepare for shutdown by closing queries and database connection
	 */
	public static function shutdown() {
		QueryHolder::closeQueries();
		self::$mysqli->close();
		self::$mysqli = null;
	}

	/**
	 * Get a twitter REST API client; will exit upon failure
	 * @return EpiTwitter instance with app+user credentials from config
	 */
	public static function getTwitterObject() {
		if(!extension_loaded("curl")) {
			self::exitWithError("Curl extension not loaded. Cannot proceed.");
		}

		$here = dirname(__FILE__);

		require_once "$here/../twitter-async/EpiCurl.php";
		require_once "$here/../twitter-async/EpiOAuth.php";
		require_once "$here/../twitter-async/EpiTwitter.php";

		$twitterObj = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_USER_TOKEN, TWITTER_USER_SECRET);
		$twitterObj->useApiVersion(1.1);

		return $twitterObj;

	}

	/**
	 * Get an array of additional users from config (ADDITIONAL_USERS named constant)
	 * @param boolean $includeReadOnlyTimelines whether to include users from ADDITIONAL_READ_ONLY_USERS
	 * @return array of additional users
	 */
	public static function getAdditionalUsers($includeReadOnlyTimelines = false) {
		$here = dirname(__FILE__);
		require_once "$here/additional_users.php";

		$results = defined('ADDITIONAL_USERS') ? create_users_array(ADDITIONAL_USERS) : array();

		if ($includeReadOnlyTimelines && defined('ADDITIONAL_READ_ONLY_USERS')) {
			$results = array_unique(array_merge($results, create_users_array(ADDITIONAL_READ_ONLY_USERS)));
		}

		return $results;
	}

	/**
	 * Get an array of the search terms to poll from config (SEARCH_TERMS named constant)
	 * @return array of additional search terms
	 */
	public static function getSearchTerms() {
		if (defined('SEARCH_TERMS')) {
			return explode(',', SEARCH_TERMS);
		}

		return [];
	}

	/**
	 * Configure timeline rows from config values
	 * @param mysqli $mysqli database handle
	 */
	public static function setupTimelines(mysqli $mysqli) {
		$here = dirname(__FILE__);
		require_once "$here/Timeline.php";

		//make a map of timelines from the database
		$timelines = Timeline::all($mysqli, true);
		$timelinesDict = [];
		foreach($timelines as $timeline) {
			$timelinesDict[$timeline->getName()] = $timeline;
		}

		//make an array of timelines we expect based on the config
		$expectedTimelines = ["home"]; //home is never optional

		if(defined("MENTIONS_TIMELINE") && MENTIONS_TIMELINE == "true") {
			$expectedTimelines[] = "mentions";
		}

		if(defined("FAVORITES_TIMELINE") && FAVORITES_TIMELINE == "true") {
			$expectedTimelines[] = "favorites";
		}

		$additionalUsers = self::getAdditionalUsers(true);
		foreach($additionalUsers as $user) {
			$expectedTimelines[] = "@$user";
		}

		$searchTerms = self::getSearchTerms();
		foreach($searchTerms as $term) {
			$expectedTimelines[] = "search: $term";
		}

		$readOnlyAdditionalUsers = array_diff($additionalUsers, self::getAdditionalUsers()); //array of users only in ADDITIONAL_READ_ONLY_USERS
		foreach($expectedTimelines as $timelineName) {
			$timelineEnabled = !in_array(substr($timelineName, 1), $readOnlyAdditionalUsers); //disable timelines only in ADDITIONAL_READ_ONLY_USERS

			if(!array_key_exists($timelineName, $timelinesDict)) {
				//new timeline from config, add to database
				self::createTimeline($timelineName, $timelineEnabled, $mysqli);
			} else {
				//timeline is in config & database
				$timeline = $timelinesDict[$timelineName];
				if($timeline->isEnabled() != $timelineEnabled) {
					//existing timeline that needs to be enabled or disabled
					$timeline->setEnabled($timelineEnabled);
					$timeline->save();
				}
				//remove as we've done any necessary processing
				unset($timelinesDict[$timelineName]);
			}
		}

		//anything left now exists in the database, but not in the config, so should be disabled
		foreach($timelinesDict as $timeline) {
			if($timeline->isEnabled()) {
				$timeline->setEnabled(false);
				$timeline->save();
			}
		}
	}

	/**
	 * Create a new timeline instance and save it
	 * @param string $name name of timeline to created
	 * @param boolean $enabled if the timeline should be marked enabled
	 * @param mysqli $mysqli database handle
	 * @return Timeline after save (with ID set)
	 */
	private static function createTimeline($name, $enabled, mysqli $mysqli) {
		$maxId = Schema::copyTweetsFromTable($name, $mysqli);
		$timeline = new Timeline($mysqli);
		$timeline->setEnabled($enabled);
		$timeline->setLastSeenId($maxId);
		$timeline->setName($name);
		$timeline->save();
		return $timeline;
	}

	/**
	 * Exit immediately printing the specified message
	 * @param string $message message to print with exit
	 */
	public static function exitWithError($message) {
		exit("ERROR: $message\n");
	}
}

?>
