<?php

require_once dirname(__FILE__).'/ConfigHelper.php';
require_once dirname(__FILE__).'/DatabaseUtils.php';
require_once dirname(__FILE__).'/HashtagUtil.php';

class Schema {

	/**
	 * Entry point for database provisioning
	 * @param mysqli $mysqli database handle
	 */
	public static function setupTables(mysqli $mysqli) {
		self::setupTweetTable($mysqli);
		self::setupUserTable($mysqli);
		self::setupTimelineTable($mysqli);
		ConfigHelper::setupTimelines($mysqli); //must migrate any legacy tables before populating hashtags
		self::setupHashtagTable($mysqli);
		self::setupFavoriteTable($mysqli);
	}

	/**
	 * Create (if non-existant) the `tweet` table; will exit upon failure
	 * @param mysqli $mysqli database handle
	 */
	private static function setupTweetTable(mysqli $mysqli) {
		if(DatabaseUtils::tableExists("tweet", $mysqli)) {
			self::assertTweetTableLongTweetReadiness($mysqli);
			return;
		}
		$create = $mysqli->query("
			CREATE TABLE IF NOT EXISTS `tweet` (
				`id` bigint(30) unsigned NOT NULL DEFAULT '0',
				`created_at` datetime NOT NULL,
				`source` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
				`in_reply_to_status_id` bigint(30) unsigned DEFAULT NULL,
				`text` varchar(3000) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
				`retweeted_by_screen_name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`retweeted_by_user_id` bigint(30) unsigned DEFAULT NULL,
				`place_full_name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`place_url` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
				`user_id` bigint(30) unsigned NOT NULL,
				`entities_json` mediumtext CHARACTER SET utf8mb4,
				`added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`display_range_start` smallint NULL,
				`display_range_end` smallint NULL,
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
	 * Modify (if necessary) the `tweet` table to allow for larger tweets
	 *
	 * - Check that `tweet`.`text` is varchar(3000)
	 * - Check that `tweet`.`display_range_start` exists
	 * - Check that `tweet`.display_range_end` exists
	 * @param mysqli $mysqli database handle
	 */
	private static function assertTweetTableLongTweetReadiness(mysqli $mysqli) {
		$textColumnLengthExpanded = DatabaseUtils::varcharColumnLength("tweet", "text", $mysqli) == 3000;
		$displayRangeStartExists = DatabaseUtils::columnExists("tweet", "display_range_start", $mysqli);
		$displayRangeEndExists = DatabaseUtils::columnExists("tweet", "display_range_end", $mysqli);

		if($textColumnLengthExpanded && $displayRangeStartExists && $displayRangeEndExists) {
			//nothing to do
			return;
		}

		if(!$textColumnLengthExpanded && !$displayRangeStartExists && !$displayRangeEndExists) {
			//tweet table needs modification for longer tweets
			echo "Altering tweet table for longer tweets...\n";
			$alter = $mysqli->query("ALTER TABLE `tweet`
									 MODIFY text VARCHAR(3000) NOT NULL,
									 ADD display_range_start smallint unsigned NULL,
									 ADD display_range_end smallint unsigned NULL");
			if(!$alter) {
				$mysqliError = $mysqli->error;
				$mysqli->close();
				ConfigHelper::exitWithError("Couldn't add tweet.display_range_start + tweet.display_range_end. $mysqliError");
			}
			return;
		}

		$mysqli->close();
		//inconsistent state
		$message = $textColumnLengthExpanded ?
			"tweet.text column expanded to varchar(3000) but tweet.display_range_start(/+)tweet.display_range_end columns missing"
			: "tweet.display_range_start(/+)tweet.display_range_end columns exist but tweet.text column is not varchar(3000)";
		ConfigHelper::exitWithError("Inconsistent state: $message");
	}

	/**
	 * Create (if non-existant) the `user` table; will exit upon failure
	 * Rename a `users` table to `user` if there is one
	 * @param mysqli $mysqli database handle
	 */
	private static function setupUserTable(mysqli $mysqli) {
		if(DatabaseUtils::tableExists("users", $mysqli)) {
			DatabaseUtils::renameTable("users", "user", $mysqli);
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
		if(DatabaseUtils::tableExists("hashtag", $mysqli)) {
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
		if(DatabaseUtils::tableExists("favorite", $mysqli)) {
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
	 * Copy tweets from old-convention table to the tweets table (if one exists)
	 * @param string $tableName name of the table to copy from
	 * @param mysqli $mysqli database handle
	 * @return highest ID from the migrated table
	 */
	public static function copyTweetsFromTable($tableName, mysqli $mysqli) {
		if(!DatabaseUtils::tableExists($tableName, $mysqli)) {
			//no old-convention table to migrate
			return null;
		}
		echo "Copying tweets from `$tableName` to `tweet`...\n";
		$migrateSQL = "INSERT IGNORE INTO `tweet`
					   SELECT id, created_at, source, in_reply_to_status_id, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, place_url, user_id, entities_json, NULL, NULL, NULL
					   FROM `$tableName`";
		$mysqli->query($migrateSQL);
		if($mysqli->error) {
			$error = $mysqli->error;
			$mysqli->close();
			ConfigHelper::exitWithError($error);
		}

		echo " -> Tweets copied: {$mysqli->affected_rows}.";
		$maxIdFromTable = $mysqli->query("SELECT MAX(id) FROM `$tableName`")->fetch_row()[0];

		$newTableName = "deletable_$tableName";
		DatabaseUtils::renameTable($tableName, $newTableName, $mysqli);
		echo " Renamed `$tableName` to `$newTableName`\n";

		return $maxIdFromTable;
	}

	/**
	 * Exit indicating a table could not be successfully created; message will include last MySQL error
	 * @param string $tableName name of table which failed to create
	 * @param mysqli $mysqli database handle
	 */
	private static function exitWithTableCreationError($tableName, mysqli $mysqli) {
		$mysqliError = $mysqli->error;
		$mysqli->close();
		ConfigHelper::exitWithError("Couldn't setup `$tableName`. $mysqliError");
	}
}

?>