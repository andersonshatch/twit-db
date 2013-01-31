<?php

class ConfigHelper {

	static function requireConfig($configPath) {
		if(is_readable($configPath)) {
			require_once $configPath;
		} else if (!file_exists($configPath)) {
			die("ERROR: Config file (config.php) doesn't exist. Visit setup.php in a browser to create it.\n");
		} else {
			die("ERROR: Config file (config.php) is not readable.");
		}
	}

	static function getDatabaseConnection() {
		if(!extension_loaded("mysqli")) {
			die("ERROR: Mysqli extension not loaded. Cannot proceed.\n");
		}

		$mysqli = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

		if($mysqli->connect_error) {
			die("ERROR: Could not connect to MySQL. {$mysqli->connect_error}\n");
		}

		$mysqli->set_charset("utf8");

		return $mysqli;
	}

	static function getTwitterObject() {
		if(!extension_loaded("curl")) {
			die("ERROR: Curl extension not loaded. Cannot proceed.\n");
		}

		$here = dirname(__FILE__);

		require_once "$here/../twitter-async/EpiCurl.php";
		require_once "$here/../twitter-async/EpiOAuth.php";
		require_once "$here/../twitter-async/EpiTwitter.php";

		$twitterObj = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_USER_TOKEN, TWITTER_USER_SECRET);
		$twitterObj->useApiVersion(1.1);

		return $twitterObj;

	}

}

?>
