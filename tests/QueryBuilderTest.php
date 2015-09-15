<?php

chdir(dirname(__FILE__));
require_once '../lib/SearchQueryBuilder.php';
/**
  * @requires extension mysqli
  */
class QueryBuilderTest extends PHPUnit_Framework_TestCase {
	private static $defaultSelectFields = "id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url";
	private static $textMatch = "MATCH(`text`) AGAINST(? IN BOOLEAN MODE)";
	private static $userSubquery = "(SELECT user_id FROM user WHERE MATCH(`screen_name`) AGAINST(?))";

	public static function setUpBeforeClass() {
		define('ADDITIONAL_USERS', 'lavamunky');
	}

	private static function sortAsc($string) {
		return str_replace("DESC", "ASC", $string);
	}

	/**
	 * Format of tests below:
	 *  1. Check SQL matches expected
	 *  2. Check parameters match expectation
	 *  3. Check reverse sort SQL matches expected
	 *  (4). (no need to check parameters for reversed sort query)
	 */

	public function testBasicQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user ORDER BY id DESC LIMIT 40";
		$inputArray = [];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals([], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testBasicCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet";
		$inputArray = [];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals([], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testBasicContinueStreamQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE id < ? ORDER BY id DESC LIMIT 40";
		$inputArray = ["max_id" => "187996142913585152"];
		$expectedParams = ["187996142913585152"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals($expectedParams, $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testBasicRefreshStreamQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE id > ? ORDER BY id DESC LIMIT 40";
		$inputArray = ["since_id" => "187996142913585152"];
		$expectedParams = ["187996142913585152"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals($expectedParams, $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testTextQueryQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "bazinga"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["bazinga", "bazinga"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testTextQueryCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE ".self::$textMatch;
		$inputArray = ["text" => "bazinga"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["bazinga"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testTextQueryContinueStreamWithRelevanceSortQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE ".self::$textMatch." AND ((".self::$textMatch." = ? AND id < ?) OR ".self::$textMatch." < ?) ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "dudes", "max_id" => "29044670385", "relevance" => "5"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["dudes", "dudes", "dudes", "5", "29044670385", "dudes", "5"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testTextQueryRefreshStreamWithRelevanceSortQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE ".self::$textMatch." AND ((".self::$textMatch." = ? AND id > ?) OR ".self::$textMatch." > ?) ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "dudes", "since_id" => "29044670385", "relevance" => "5"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["dudes", "dudes", "dudes", "5", "29044670385", "dudes", 5], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testUsernameWithRetweetsQueryQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "andersonshatch", "retweets" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["andersonshatch", "andersonshatch"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testUsernameWithRetweetsQueryCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.")";
		$inputArray = ["username" => "andersonshatch", "retweets" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["andersonshatch", "andersonshatch"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testUsernameWithRetweetsContinueQueryQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND id < ? ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "kklaven", "retweets" => "on", "max_id" => 123456789];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["kklaven", "kklaven", "123456789"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testUsernameWithRetweetsRefreshQueryQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND id > ? ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "kklaven", "retweets" => "on", "since_id" => 123456789];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["kklaven", "kklaven", "123456789"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);

	}

	public function testUsernameWithRetweetsInAdditionalUsersCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.")";
		$inputArray = ["username" => "Lavamunky", "retweets" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["lavamunky", "lavamunky"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testUsernameOnlyQueryQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "BABBanator"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["babbanator"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testUsernameOnlyQueryCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE user_id = ".self::$userSubquery;
		$inputArray = ["username" => "clarkykestrel"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["clarkykestrel"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testUsernameOnlyQueryInAdditionalUsersQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["lavamunky"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testUsernameOnlyQueryInAdditionalUsersCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE user_id = ".self::$userSubquery;
		$inputArray = ["username" => "lavamunky"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["lavamunky"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testTextAndUsernameQueryQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." AND ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "crittweets", "text" => "homeland"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["homeland", "crittweets", "homeland"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testTextAndUsernameQueryCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE user_id = ".self::$userSubquery." AND ".self::$textMatch;
		$inputArray = ["username" => "crittweets", "text" => "homeland"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["crittweets", "homeland"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testTextAndUsernameQueryInAdditionalUsersQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." AND ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky", "text" => "ncis"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["ncis", "lavamunky", "ncis"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testTextAndUsernameQueryInAdditionalUsersCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE user_id = ".self::$userSubquery." AND ".self::$textMatch;
		$inputArray = ["username" => "lavamunky", "text" => "ncis"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["lavamunky", "ncis"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testTextAndUsernameWithRetweetsQueryQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "fuckyeahlost", "text" => "dharma", "retweets" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["dharma", "fuckyeahlost", "fuckyeahlost", "dharma"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testTextAndUsernameWithRetweetsQueryCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND ".self::$textMatch;
		$inputArray = ["username" => "fuckyeahlost", "text" => "we have to go back", "retweets" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["fuckyeahlost", "fuckyeahlost", "we have to go back"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testTextAndUsernameInAdditionalUsersWithRetweetsQueryQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky", "text" => "linux", "retweets" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["linux", "lavamunky", "lavamunky", "linux"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery(array("username" => "lavamunky", "text" => "linux", "retweets" => "on"), false, true)[0]);
	}

	public function testTextAndUsernameInAdditionalUsersWithRetweetsQueryCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND ".self::$textMatch;
		$inputArray = ["username" => "lavamunky", "text" => "twitter", "retweets" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["lavamunky", "lavamunky", "twitter"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testAtMentionsQueryWithMentionsDisabledQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "@me"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testAtMentionsQueryWithMentionsDisabledCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE user_id = ".self::$userSubquery;
		$inputArray = ["username" => "@me"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testAtMentionsQueryWithMentionsEnabledQueryBuild() {
		define('MENTIONS_TIMELINE', true);
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "@me"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["@me"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testAtMentionsQueryWithMentionsEnabledCountQueryBuild() {
		$expectedQuery = "SELECT COUNT(1) FROM tweet WHERE user_id = ".self::$userSubquery;
		$inputArray = ["username" => "@me"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["@me"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, true, true)[0]);
	}

	public function testExcludeRepliesQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields." FROM tweet NATURAL JOIN user WHERE text NOT LIKE '@%' ORDER BY id DESC LIMIT 40";
		$inputArray = ["exclude_replies" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals([], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}

	public function testExcludeRepliesWithTextSearchQueryBuild() {
		$expectedQuery = "SELECT ".self::$defaultSelectFields.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE ".self::$textMatch." AND text NOT LIKE '@%' ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "Fringe", "exclude_replies" => "on"];
		$result = SearchQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals(["Fringe", "Fringe"], $result[1]);
		$this->assertEquals(self::sortAsc($expectedQuery), SearchQueryBuilder::buildQuery($inputArray, false, true)[0]);
	}
}

?>
