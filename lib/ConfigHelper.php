<?php

class ConfigHelper {

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
			self::setupTables($mysqli);
		}

		return $mysqli;
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
	 * Entry point for database provisioning
	 * @param mysqli $mysqli database handle
	 */
	private static function setupTables(mysqli $mysqli) {
		self::setupTweetTable($mysqli);
		self::setupUserTable($mysqli);
		self::setupTimelineTable($mysqli);
		self::setupTimelines($mysqli);
		self::setupHashtagTable($mysqli);
		self::setupFavoriteTable($mysqli);
	}

	/**
	 * Create (if non-existant) the `tweet` table; will exit upon failure
	 * @param mysqli $mysqli database handle
	 */
	private static function setupTweetTable(mysqli $mysqli) {
		$create = $mysqli->query("
			CREATE TABLE IF NOT EXISTS `tweet` (
				`id` bigint(30) unsigned NOT NULL DEFAULT '0',
				`created_at` datetime NOT NULL,
				`source` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
				`in_reply_to_status_id` bigint(30) unsigned DEFAULT NULL,
				`text` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
				`retweeted_by_screen_name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`retweeted_by_user_id` bigint(30) unsigned DEFAULT NULL,
				`place_full_name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`place_url` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`user_id` bigint(30) unsigned NOT NULL,
				`entities_json` mediumtext CHARACTER SET utf8mb4,
				`added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `user_id_index` (`user_id`),
				KEY `in_reply_to_status_id_index` (`in_reply_to_status_id`),
				KEY `retweeted_by_user_id_index` (`retweeted_by_user_id`),
				FULLTEXT KEY `text_fulltext_index` (`text`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
		if(!$create) {
			self::exitWithTableCreationError("tweet", $mysqli);
		}
	}

	/**
	 * Create (if non-existant) the `user` table; will exit upon failure
	 * Rename a `users` table to `user` if there is one
	 * @param mysqli $mysqli database handle
	 */
	private static function setupUserTable(mysqli $mysqli) {
		if(self::tableExists("users", $mysqli)) {
			self::renameTable("users", "user", $mysqli);
			return;
		}

		$create = $mysqli->query("
			CREATE TABLE IF NOT EXISTS `user` (
				`screen_name` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
				`user_id` bigint(20) unsigned NOT NULL,
				`description` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`location` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`followers_count` int(10) DEFAULT NULL,
				`friends_count` int(10) DEFAULT NULL,
				`statuses_count` int(10) DEFAULT NULL,
				`url` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`profile_image_url` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`user_created_at` datetime DEFAULT NULL,
				`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				`verified` tinyint(1) NOT NULL DEFAULT '0',
				`protected` tinyint(1) NOT NULL DEFAULT '0',
				 PRIMARY KEY (`user_id`),
				 FULLTEXT KEY `index` (`screen_name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
		if(!$create) {
			self::exitWithTableCreationError("user", $mysqli);
		}
	}

	/**
	 * Create (if non-existant) the `timeline` table; will exit upon failure
	 * @param mysqli $mysqli database handle
	 */
	private static function setupTimelineTable(mysqli $mysqli) {
		$create = $mysqli->query("
			CREATE TABLE IF NOT EXISTS `timeline` (
			   `timeline_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			   `name` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
			   `last_seen_id` bigint(30) unsigned DEFAULT NULL,
			   `enabled` tinyint(1) NOT NULL DEFAULT '1',
			   `last_updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			   PRIMARY KEY (`timeline_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
		if(!$create) {
			self::exitWithTableCreationError("timeline", $mysqli);
		}
	}

	/**
	 * Create (if non-existant) the `hashtag` table; will exit upon failure
	 * @param mysqli $mysqli database handle
	 */
	private static function setupHashtagTable(mysqli $mysqli) {
		if(self::tableExists("hashtag", $mysqli)) {
			return;
		}

		$create = $mysqli->query("
			CREATE TABLE IF NOT EXISTS `hashtag` (
			   `name` varchar(140) NOT NULL DEFAULT '',
			   `first_seen` datetime NOT NULL,
			   `last_seen` datetime NOT NULL,
			   `usage_count` INT(11) NOT NULL DEFAULT '1',
			   PRIMARY KEY (`name`),
			   FULLTEXT KEY `name_fulltext_index` (`name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
		if(!$create) {
			self::exitWithTableCreationError("hashtag", $mysqli);
		}

		require_once dirname(__FILE__).'/HashtagUtil.php';
		echo "Populating hashtag table...\n";
		HashtagUtil::populateHashtagTable($mysqli);
	}

	/**
	 * Create (if non-existant) the `favorite` table; truncate it if it does exist...
	 * ...Since favorites can be added in a non-linear fashion, we have to page through them all each time to ensure nothing's missed
	 * @param mysqli $mysqli database handle
	 */
	private static function setupFavoriteTable(mysqli $mysqli) {
		if(self::tableExists("favorite", $mysqli)) {
			$mysqli->query("TRUNCATE TABLE `favorite`");
		} else {
			$create = $mysqli->query("
			CREATE TABLE IF NOT EXISTS `favorite` (
				`id` bigint(30) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
			if(!$create) {
				self::exitWithTableCreationError("favorite", $mysqli);
			}
		}
	}

	/**
	 * Configure timeline rows from config values
	 * @param mysqli $mysqli database handle
	 */
	private static function setupTimelines(mysqli $mysqli) {
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
		$maxId = self::copyTweetsFromTable($name, $mysqli);
		$timeline = new Timeline($mysqli);
		$timeline->setEnabled($enabled);
		$timeline->setLastSeenId($maxId);
		$timeline->setName($name);
		$timeline->save();
		return $timeline;
	}

	/**
	 * See if a table exists in this database
	 * @param string $tableName name of the table to check for
	 * @param mysqli $mysqli database handle
	 * @return true if a table with $tableName exists in this database
	 */
	private static function tableExists($tableName, mysqli $mysqli) {
		$tableExistsSQL = "SELECT 1 FROM information_schema.tables
						   WHERE table_schema = '".DB_NAME."'
						   AND table_name = '".$tableName."'";
		if(!$mysqli->query($tableExistsSQL)->fetch_all()) {
			return false;
		}

		return true;
	}

	/**
	 * Copy tweets from old-convention table to the tweets table (if one exists)
	 * @param string $tableName name of the table to copy from
	 * @param mysqli $mysqli database handle
	 * @return highest ID from the migrated table
	 */
	private static function copyTweetsFromTable($tableName, mysqli $mysqli) {
		if(!self::tableExists($tableName, $mysqli)) {
			//no old-convention table to migrate
			return null;
		}
		echo "Copying tweets from `$tableName` to `tweet`\n";
		$migrateSQL = "INSERT IGNORE INTO `tweet`
					   SELECT id, created_at, source, in_reply_to_status_id, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, place_url, user_id, entities_json, NULL
					   FROM `$tableName`";
		$mysqli->query($migrateSQL);
		if($mysqli->error) {
			$error = $mysqli->error;
			$mysqli->close();
			self::exitWithError($error);
		}

		echo " -> Tweets copied: {$mysqli->affected_rows}.";
		$maxIdFromTable = $mysqli->query("SELECT MAX(id) FROM `$tableName`")->fetch_row()[0];

		$newTableName = "deletable_$tableName";
		self::renameTable($tableName, $newTableName, $mysqli);
		echo " Renamed `$tableName` to `$newTableName`\n";

		return $maxIdFromTable;
	}

	/**
	 * Rename a table
	 * @param string $currentTableName name of the table to be renamed
	 * @param string $newTableName name to rename the table to
	 * @param mysqli $mysqli database handle
	 */
	private static function renameTable($currentTableName, $newTableName, mysqli $mysqli) {
		$mysqli->query("RENAME TABLE `$currentTableName` TO `$newTableName`");
	}

	/**
	 * Exit indicating a table could not be successfully created; message will include last MySQL error
	 * @param string $tableName name of table which failed to create
	 * @param mysqli $mysqli database handle
	 */
	private static function exitWithTableCreationError($tableName, mysqli $mysqli) {
		$mysqliError = $mysqli->error;
		$mysqli->close();
		self::exitWithError("Couldn't setup `$tableName`. $mysqliError");
	}

	/**
	 * Exit immediately printing the specified message
	 * @param string $message message to print with exit
	 */
	private static function exitWithError($message) {
		exit("ERROR: $message\n");
	}
}

?>
