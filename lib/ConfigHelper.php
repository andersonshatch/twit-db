<?php

class ConfigHelper {

	static function requireConfig($configPath) {
		if(is_readable($configPath)) {
			require_once $configPath;
		} else if (!file_exists($configPath)) {
			exit("ERROR: Config file (config.php) doesn't exist. Visit setup.php in a browser to create it.\n");
		} else {
			exit("ERROR: Config file (config.php) is not readable.");
		}
	}

	static function getDatabaseConnection() {
		if(!extension_loaded("mysqli")) {
			exit("ERROR: Mysqli extension not loaded. Cannot proceed.\n");
		}

		$mysqli = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

		if($mysqli->connect_error) {
			exit("ERROR: Could not connect to MySQL. {$mysqli->connect_error}\n");
		}

		if(!@$mysqli->set_charset("utf8mb4") || $mysqli->error) {
			exit("ERROR: Could not select utf8mb4 charset. {$mysqli->error}. See https://github.com/andersonshatch/twit-db/issues/7\n");
		}

		self::checkTables($mysqli);

		return $mysqli;
	}

	static function getTwitterObject() {
		if(!extension_loaded("curl")) {
			exit("ERROR: Curl extension not loaded. Cannot proceed.\n");
		}

		$here = dirname(__FILE__);

		require_once "$here/../twitter-async/EpiCurl.php";
		require_once "$here/../twitter-async/EpiOAuth.php";
		require_once "$here/../twitter-async/EpiTwitter.php";

		$twitterObj = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_USER_TOKEN, TWITTER_USER_SECRET);
		$twitterObj->useApiVersion(1.1);

		return $twitterObj;

	}

	static function getAdditionalUsers() {
		$here = dirname(__FILE__);
		require_once "$here/additional_users.php";

		return create_users_array(ADDITIONAL_USERS);
	}

	private static function checkTables($mysqli) {
		$tables = array("home", "users", "mentions");

		$additionalUsers = self::getAdditionalUsers();

		foreach($additionalUsers as $user) {
			$tables[] = "@$user";
		}

		$collationSQL = "SELECT table_name
                FROM information_schema.tables
                WHERE table_collation <> 'utf8mb4_bin'
                AND table_schema = '".DB_NAME."'
				AND table_name in (".implode(",", array_map(function($string) { return "'$string'";}, $tables)).")";

		$tablesToUpdate = $mysqli->query($collationSQL)->fetch_all();

		if(empty($tablesToUpdate)) {
			return;
		}

		foreach($tablesToUpdate as $table) {
			$tableName = $table[0];
			echo "Modifying collation of $tableName\n";

			if ($tableName == "users") {
				$changeCollationSQL = "ALTER TABLE `users`
						CHANGE `screen_name`       `screen_name`       VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
						CHANGE `description`       `description`       VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
						CHANGE `location`          `location`          VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
						CHANGE `name`              `name`              VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
						CHANGE `url`               `url`               VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
						CHANGE `profile_image_url` `profile_image_url` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin";
			} else {
				$changeCollationSQL = "ALTER TABLE `$tableName` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin";
			}

			$start = microtime(true);
			$mysqli->query($changeCollationSQL);
			$end = microtime(true);
			$time = $end - $start;
			echo "Done: {$mysqli->info}. Took $time seconds. ({$mysqli->error})\n";
		}
	}
}

?>
