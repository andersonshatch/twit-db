<?php

chdir(dirname(__FILE__));
require_once '../lib/buildQuery.php';
/**
  * @requires extension mysqli
  */
class QueryBuilderTest extends PHPUnit_Framework_TestCase {
	private static $mysqli;
	private static $defaultSelectFields = "id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url";

	public static function setUpBeforeClass() {
		self::$mysqli = false;
		if(extension_loaded('mysqli'))
			@self::$mysqli = new mysqli('127.0.0.1', 'test', 'password', 'twitdb_test');

		define('ADDITIONAL_USERS', 'lavamunky');
	}

	public function assertPreConditions() {
		if(!self::$mysqli) {
			$this->markTestSkipped('No mysqli extension');
		}

		if(self::$mysqli->connect_error) {
			echo self::$mysqli->connect_error."\n";
			$this->markTestSkipped('Could not connect to MySQL, skipping build query tests');
		}
	}

	public static function tearDownAfterClass() {
		if(self::$mysqli)
			@self::$mysqli->close();
	}

	private static function textMatch($string) {
		return "MATCH(`text`) AGAINST('$string' IN BOOLEAN MODE)";
	}

	private static function userMatch($string) {
		return "MATCH(`screen_name`) AGAINST('$string')";
	}

	private static function sortAsc($string) {
		return str_replace("DESC", "ASC", $string);
	}

	public function testBasicQueryBuild() {
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` ORDER BY id DESC LIMIT 40";
		$inputArray = [];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testBasicCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `tweets`";
		$inputArray = [];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testBasicContinueStreamQueryBuild() {
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` WHERE id < 187996142913585152 ORDER BY id DESC LIMIT 40";
		$inputArray = ["max_id" => "187996142913585152"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testTextQueryQueryBuild() {
		$textMatch = self::textMatch("bazinga");
		$expected = "SELECT ".self::$defaultSelectFields.", $textMatch as relevance FROM `tweets` NATURAL JOIN `users` WHERE $textMatch ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "bazinga"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testTextQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE ".self::textMatch("bazinga")."";
		$inputArray = ["text" => "bazinga"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testTextQueryContinueStreamWithRelevanceSortQueryBuild() {
		$textMatch = self::textMatch("dudes");
		$expected = "SELECT ".self::$defaultSelectFields.", $textMatch as relevance FROM `tweets` NATURAL JOIN `users` WHERE $textMatch AND (($textMatch = 5 AND id < 29044670385) OR $textMatch < 5) ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "dudes", "max_id" => "29044670385", "relevance" => "5"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testUsernameWithRetweetsQueryQueryBuild() {
		$userMatch = self::userMatch("andersonshatch");
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE $userMatch) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE $userMatch)) ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "andersonshatch", "retweets" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testUsernameWithRetweetsQueryCountQueryBuild() {
		$userMatch = self::userMatch("andersonshatch");
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE (user_id = (SELECT user_id FROM users WHERE $userMatch) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE $userMatch))";
		$inputArray = ["username" => "andersonshatch", "retweets" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testUsernameWithRetweetsContinueQueryQueryBuild() {
		$userMatch = self::userMatch("kklaven");
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE $userMatch) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE $userMatch)) AND id < 123456789 ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "kklaven", "retweets" => "on", "max_id" => 123456789];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testUsernameWithRetweetsInAdditionalUsersCountQueryBuild() {
		$userMatch = self::userMatch("lavamunky");
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE (user_id = (SELECT user_id FROM users WHERE $userMatch) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE $userMatch))";
		$inputArray = ["username" => "Lavamunky", "retweets" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testUsernameOnlyQueryQueryBuild() {
		$userMatch = self::userMatch("babbanator");
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE $userMatch) ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "BABBanator"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testUsernameOnlyQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("clarkykestrel").")";
		$inputArray = ["username" => "clarkykestrel"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testUsernameOnlyQueryInAdditionalUsersQueryBuild() {
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("lavamunky").") ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testUsernameOnlyQueryInAdditionalUsersCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("lavamunky").")";
		$inputArray = ["username" => "lavamunky"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testTextAndUsernameQueryQueryBuild() {
		$textMatch = self::textMatch("homeland");
		$expected = "SELECT ".self::$defaultSelectFields.", $textMatch as relevance FROM `tweets` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("crittweets").") AND $textMatch ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "crittweets", "text" => "homeland"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testTextAndUsernameQueryCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("crittweets").") AND ".self::textMatch("homeland")."";
		$inputArray = ["username" => "crittweets", "text" => "homeland"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testTextAndUsernameQueryInAdditionalUsersQueryBuild() {
		$textMatch = self::textMatch("ncis");
		$expected = "SELECT ".self::$defaultSelectFields.", $textMatch as relevance FROM `tweets` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("lavamunky").") AND $textMatch ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky", "text" => "ncis"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testTextAndUsernameQueryInAdditionalUsersCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("lavamunky").") AND ".self::textMatch("ncis")."";
		$inputArray = ["username" => "lavamunky", "text" => "ncis"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testTextAndUsernameWithRetweetsQueryQueryBuild() {
		$textMatch = self::textMatch("dharma");
		$userMatch = self::userMatch("fuckyeahlost");
		$expected = "SELECT ".self::$defaultSelectFields.", $textMatch as relevance FROM `tweets` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE $userMatch) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE $userMatch)) AND $textMatch ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "fuckyeahlost", "text" => "dharma", "retweets" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testTextAndUsernameWithRetweetsQueryCountQueryBuild() {
		$userMatch = self::userMatch("fuckyeahlost");
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE (user_id = (SELECT user_id FROM users WHERE $userMatch) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE $userMatch)) AND MATCH(`text`) AGAINST('we have to go back' IN BOOLEAN MODE)";
		$inputArray = ["username" => "fuckyeahlost", "text" => "we have to go back", "retweets" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testTextAndUsernameInAdditionalUsersWithRetweetsQueryQueryBuild() {
		$textMatch = self::textMatch("linux");
		$userMatch = self::userMatch("lavamunky");
		$expected = "SELECT ".self::$defaultSelectFields.", $textMatch as relevance FROM `tweets` NATURAL JOIN `users` WHERE (user_id = (SELECT user_id FROM users WHERE $userMatch) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE $userMatch)) AND $textMatch ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky", "text" => "linux", "retweets" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery(array("username" => "lavamunky", "text" => "linux", "retweets" => "on"), self::$mysqli, false, true));
	}

	public function testTextAndUsernameInAdditionalUsersWithRetweetsQueryCountQueryBuild() {
		$userMatch = self::userMatch("lavamunky");
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE (user_id = (SELECT user_id FROM users WHERE $userMatch) OR retweeted_by_user_id = (SELECT user_id FROM users WHERE $userMatch)) AND ".self::textMatch("twitter")."";
		$inputArray = ["username" => "lavamunky", "text" => "twitter", "retweets" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testAtMentionsQueryWithMentionsDisabledQueryBuild() {
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("@me").") ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "@me"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testAtMentionsQueryWithMentionsDisabledCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("@me").")";
		$inputArray = ["username" => "@me"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery(array("username" => "@me"), self::$mysqli, true, true));
	}

	public function testAtMentionsQueryWithMentionsEnabledQueryBuild() {
		define('MENTIONS_TIMELINE', true);
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("@me").") ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "@me"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testAtMentionsQueryWithMentionsEnabledCountQueryBuild() {
		$expected = "SELECT COUNT(1) FROM `tweets` WHERE user_id = (SELECT user_id FROM users WHERE ".self::userMatch("@me").")";
		$inputArray = ["username" => "@me"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli, true));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, true, true));
	}

	public function testExcludeRepliesQueryBuild() {
		$expected = "SELECT ".self::$defaultSelectFields." FROM `tweets` NATURAL JOIN `users` WHERE text NOT LIKE '@%' ORDER BY id DESC LIMIT 40";
		$inputArray = ["exclude_replies" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}

	public function testExcludeRepliesWithTextSearchQueryBuild() {
		$textMatch = self::textMatch("Fringe");
		$expected = "SELECT ".self::$defaultSelectFields.", $textMatch as relevance FROM `tweets` NATURAL JOIN `users` WHERE $textMatch AND text NOT LIKE '@%' ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "Fringe", "exclude_replies" => "on"];
		$this->assertEquals($expected, buildQuery($inputArray, self::$mysqli));
		$this->assertEquals(self::sortAsc($expected), buildQuery($inputArray, self::$mysqli, false, true));
	}
}

?>
