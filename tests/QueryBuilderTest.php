<?php

chdir(dirname(__FILE__));
require_once '../lib/buildQuery.php';
/**
  * @requires extension mysqli
  */
class QueryBuilderTest extends PHPUnit_Framework_TestCase {
	private static $mysqli;

	public static function setUpBeforeClass() {
		@self::$mysqli = new mysqli('127.0.0.1', 'test', 'password', 'twitdb_test');

		if(self::$mysqli->connect_error) {
			$this->markTestSkipped('Could not connect to MySQL, skipping build query tests');
		}
		define('ADDITIONAL_USERS', 'lavamunky');
	}

	public static function tearDownAfterClass() {
		@self::$mysqli->close();
	}

	public function testBasicQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url FROM `home` NATURAL JOIN `users` ORDER BY id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array(), self::$mysqli));
	}

	public function testBasicCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `home` NATURAL JOIN `users`";
		$this->assertEquals($expected, buildQuery(array(), self::$mysqli, true));
	}

	public function testBasicContinueStreamQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url FROM `home` NATURAL JOIN `users` WHERE id < 187996142913585152 ORDER BY id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("max_id" => "187996142913585152"), self::$mysqli));
	}

	public function testTextQueryQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url, MATCH(`text`) AGAINST('bazinga' IN BOOLEAN MODE) as relevance FROM `home` NATURAL JOIN `users` WHERE MATCH(`text`) AGAINST('bazinga' IN BOOLEAN MODE) ORDER BY relevance DESC, id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("text" => "bazinga"), self::$mysqli));
	}

	public function testTextQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `home` NATURAL JOIN `users` WHERE MATCH(`text`) AGAINST('bazinga' IN BOOLEAN MODE)";
		$this->assertEquals($expected, buildQuery(array("text" => "bazinga"), self::$mysqli, true));
	}

	public function testTextQueryContinueStreamWithRelevanceSortQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url, MATCH(`text`) AGAINST('dudes' IN BOOLEAN MODE) as relevance FROM `home` NATURAL JOIN `users` WHERE MATCH(`text`) AGAINST('dudes' IN BOOLEAN MODE) AND ((MATCH(`text`) AGAINST('dudes' IN BOOLEAN MODE) = 5 AND id < 29044670385) OR MATCH(`text`) AGAINST('dudes' IN BOOLEAN MODE) < 5) ORDER BY relevance DESC, id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("text" => "dudes", "max_id" => "29044670385", "relevance" => "5"), self::$mysqli));
	}

	public function testUsernameWithRetweetsQueryQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url FROM `home` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('andersonshatch')) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('andersonshatch'))) ORDER BY id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "andersonshatch", "retweets" => "on"), self::$mysqli));
	}

	public function testUsernameWithRetweetsQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `home` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('andersonshatch')) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('andersonshatch')))";
		$this->assertEquals($expected, buildQuery(array("username" => "andersonshatch", "retweets" => "on"), self::$mysqli, true));
	}

	public function testUsernameWithRetweetsContinueQueryQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url FROM `home` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('kklaven')) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('kklaven'))) AND id < 123456789 ORDER BY id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "kklaven", "retweets" => "on", "max_id" => 123456789), self::$mysqli));
	}

	public function testUsernameWithRetweetsInAdditionalUsersCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `@lavamunky` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky')) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky')))";
		$this->assertEquals($expected, buildQuery(array("username" => "Lavamunky", "retweets" => "on"), self::$mysqli, true));
	}
	public function testUsernameOnlyQueryQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url FROM `home` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('babbanator')) ORDER BY id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "BABBanator"), self::$mysqli));
	}

	public function testUsernameOnlyQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `home` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('clarkykestrel'))";
		$this->assertEquals($expected, buildQuery(array("username" => "clarkykestrel"), self::$mysqli, true));
	}

	public function testUsernameOnlyQueryInAdditionalUsersQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url FROM `@lavamunky` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky')) ORDER BY id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "lavamunky"), self::$mysqli));
	}

	public function testUsernameOnlyQueryInAdditionalUsersCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `@lavamunky` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky'))";
		$this->assertEquals($expected, buildQuery(array("username" => "lavamunky"), self::$mysqli, true));
	}

	public function testTextAndUsernameQueryQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url, MATCH(`text`) AGAINST('homeland' IN BOOLEAN MODE) as relevance FROM `home` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('crittweets')) AND MATCH(`text`) AGAINST('homeland' IN BOOLEAN MODE) ORDER BY relevance DESC, id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "crittweets", "text" => "homeland"), self::$mysqli));
	}

	public function testTextAndUsernameQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `home` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('crittweets')) AND MATCH(`text`) AGAINST('homeland' IN BOOLEAN MODE)";
		$this->assertEquals($expected, buildQuery(array("username" => "crittweets", "text" => "homeland"), self::$mysqli, true));
	}

	public function testTextAndUsernameQueryInAdditionalUsersQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url, MATCH(`text`) AGAINST('ncis' IN BOOLEAN MODE) as relevance FROM `@lavamunky` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky')) AND MATCH(`text`) AGAINST('ncis' IN BOOLEAN MODE) ORDER BY relevance DESC, id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "lavamunky", "text" => "ncis"), self::$mysqli));
	}

	public function testTextAndUsernameQueryInAdditionalUsersCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `@lavamunky` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky')) AND MATCH(`text`) AGAINST('ncis' IN BOOLEAN MODE)";
		$this->assertEquals($expected, buildQuery(array("username" => "lavamunky", "text" => "ncis"), self::$mysqli, true));
	}

	public function testTextAndUsernameWithRetweetsQueryQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url, MATCH(`text`) AGAINST('dharma' IN BOOLEAN MODE) as relevance FROM `home` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('fuckyeahlost')) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('fuckyeahlost'))) AND MATCH(`text`) AGAINST('dharma' IN BOOLEAN MODE) ORDER BY relevance DESC, id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "fuckyeahlost", "text" => "dharma", "retweets" => "on"), self::$mysqli));
	}

	public function testTextAndUsernameWithRetweetsQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `home` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('fuckyeahlost')) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('fuckyeahlost'))) AND MATCH(`text`) AGAINST('we have to go back' IN BOOLEAN MODE)";
		$this->assertEquals($expected, buildQuery(array("username" => "fuckyeahlost", "text" => "we have to go back", "retweets" => "on"), self::$mysqli, true));
	}

	public function testTextAndUsernameInAdditionalUsersWithRetweetsQueryQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url, MATCH(`text`) AGAINST('linux' IN BOOLEAN MODE) as relevance FROM `@lavamunky` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky')) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky'))) AND MATCH(`text`) AGAINST('linux' IN BOOLEAN MODE) ORDER BY relevance DESC, id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "lavamunky", "text" => "linux", "retweets" => "on"), self::$mysqli));
	}

	public function testTextAndUseranemInAdditionalUsersWithRetweetsQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `@lavamunky` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky')) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('lavamunky'))) AND MATCH(`text`) AGAINST('twitter' IN BOOLEAN MODE)";
		$this->assertEquals($expected, buildQuery(array("username" => "lavamunky", "text" => "twitter", "retweets" => "on"), self::$mysqli, true));
	}

	public function testAtMentionsQueryWithMentionsDisabledQueryBuild() {
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url FROM `home` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('@me')) ORDER BY id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "@me"), self::$mysqli));
	}

	public function testAtMentionsQueryWithMentionsDisabledCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `home` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('@me'))";
		$this->assertEquals($expected, buildQuery(array("username" => "@me"), self::$mysqli, true));
	}

	public function testAtMentionsQueryWithMentionsEnabledQueryBuild() {
		define('MENTIONS_TIMELINE', true);
		$expected = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url FROM `mentions` NATURAL JOIN `users` ORDER BY id DESC LIMIT 40";
		$this->assertEquals($expected, buildQuery(array("username" => "@me"), self::$mysqli));
	}

	public function testAtMentionsQueryWithMentionsEnabledCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `mentions` NATURAL JOIN `users`";
		$this->assertEquals($expected, buildQuery(array("username" => "@me"), self::$mysqli, true));
	}
}

?>
