<?php

chdir(dirname(__FILE__));
require_once '../lib/SearchQueryBuilder.php';
/**
  * @requires extension mysqli
  */
class QueryBuilderTest extends PHPUnit_Framework_TestCase {
	private static $countSelect = "SELECT COUNT(1) FROM tweet";
	private static $defaultSelect = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url";
	private static $textMatch = "MATCH(`text`) AGAINST(? IN BOOLEAN MODE)";
	private static $userSubquery = "(SELECT user_id FROM user WHERE MATCH(`screen_name`) AGAINST(?))";

	public static function setUpBeforeClass() {
		define('ADDITIONAL_USERS', 'lavamunky');
	}

	/**
	 * Replace DESC with ASC in a string
	 * @param $string string to replace DESC in
	 * @return string $string with DESC replaced by ASC
	 */
	private static function sortAsc($string) {
		return str_replace("DESC", "ASC", $string);
	}

	/**
	 * Add favorite join SQL to a given SQL query
	 *
	 * Replaces "FROM tweet" with "FROM tweet NATURAL JOIN favorite"
	 * and
	 * replaces "FROM tweet NATURAL JOIN user" with "FROM tweet NATURAL JOIN user NATURAL JOIN favorite"
	 *
	 * @param $string query to perform replacements on
	 * @return string replaced query
	 */
	private static function addFavoriteJoin($string) {
		return preg_replace("/(FROM tweet)( NATURAL JOIN user)?/", "$1$2 NATURAL JOIN favorite", $string);
	}

	/**
	 * Assert the various permutations for each query
	 * 1. SQL matches
	 * 2. Query parameters match
	 * 3. Reverse sort SQL matches
	 * 4. Favorite query matches
	 *
	 *
	 * @param $expectedQuery expected SQL query that will be built
	 * @param $expectedParams expected parameters array for query
	 * @param $inputArray array to build query from
	 * @param bool|false $isCountQuery true if query should be a COUNT query
	 */
	private function assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, $isCountQuery = false) {
		$result = SearchQueryBuilder::buildQuery($inputArray, $isCountQuery);
		$this->assertEquals($expectedQuery, $result[0]);                                    //SQL match
		$this->assertEquals($result[1], $expectedParams);                                   //parameter match

		$result = SearchQueryBuilder::buildQuery($inputArray, $isCountQuery, true);
		$this->assertEquals(self::sortAsc($expectedQuery), $result[0]);                     //reverse sort SQL match
		$this->assertEquals($result[1], $expectedParams);                                   //reverse sort parameter match

		//favorites setup
		$inputArray["favorites_only"] = "on";
		$expectedQuery = self::addFavoriteJoin($expectedQuery);
		$result = SearchQueryBuilder::buildQuery($inputArray, $isCountQuery);

		$this->assertEquals($expectedQuery, $result[0]);                                    //favorites SQL match
		$this->assertEquals($result[1], $expectedParams);                                   //favorites parameter match

		$result = SearchQueryBuilder::buildQuery($inputArray, $isCountQuery, true);
		$this->assertEquals(self::sortAsc($expectedQuery), $result[0]);                     //favorites reverse sort SQL match
		$this->assertEquals($result[1], $expectedParams);                                   //favorites reverse sort parameter match
	}

	public function testBasicQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user ORDER BY id DESC LIMIT 40";
		$inputArray = [];
		$expectedParams = [];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testBasicCountQueryBuild() {
		$expectedQuery = self::$countSelect."";
		$inputArray = [];
		$expectedParams = [];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testBasicContinueStreamQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE id < ? ORDER BY id DESC LIMIT 40";
		$inputArray = ["max_id" => "187996142913585152"];
		$expectedParams = ["187996142913585152"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testBasicRefreshStreamQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE id > ? ORDER BY id DESC LIMIT 40";
		$inputArray = ["since_id" => "187996142913585152"];
		$expectedParams = ["187996142913585152"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testTextQueryQueryBuild() {
		$expectedQuery = self::$defaultSelect.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "bazinga"];
		$expectedParams = ["bazinga", "bazinga"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testTextQueryCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE ".self::$textMatch;
		$inputArray = ["text" => "bazinga"];
		$expectedParams = ["bazinga"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testTextQueryContinueStreamWithRelevanceSortQueryBuild() {
		$expectedQuery = self::$defaultSelect.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE ".self::$textMatch." AND ((".self::$textMatch." = ? AND id < ?) OR ".self::$textMatch." < ?) ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "dudes", "max_id" => "29044670385", "relevance" => "5"];
		$expectedParams = ["dudes", "dudes", "dudes", "5", "29044670385", "dudes", "5"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testTextQueryRefreshStreamWithRelevanceSortQueryBuild() {
		$expectedQuery = self::$defaultSelect.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE ".self::$textMatch." AND ((".self::$textMatch." = ? AND id > ?) OR ".self::$textMatch." > ?) ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "dudes", "since_id" => "29044670385", "relevance" => "5"];
		$expectedParams = ["dudes", "dudes", "dudes", "5", "29044670385", "dudes", 5];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testUsernameWithRetweetsQueryQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "andersonshatch", "retweets" => "on"];
		$expectedParams = ["andersonshatch", "andersonshatch"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testUsernameWithRetweetsQueryCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.")";
		$inputArray = ["username" => "andersonshatch", "retweets" => "on"];
		$expectedParams = ["andersonshatch", "andersonshatch"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testUsernameWithRetweetsContinueQueryQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND id < ? ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "kklaven", "retweets" => "on", "max_id" => 123456789];
		$expectedParams = ["kklaven", "kklaven", "123456789"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testUsernameWithRetweetsRefreshQueryQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND id > ? ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "kklaven", "retweets" => "on", "since_id" => 123456789];
		$expectedParams = ["kklaven", "kklaven", "123456789"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testUsernameWithRetweetsInAdditionalUsersCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.")";
		$inputArray = ["username" => "Lavamunky", "retweets" => "on"];
		$expectedParams = ["lavamunky", "lavamunky"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testUsernameOnlyQueryQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "BABBanator"];
		$expectedParams = ["babbanator"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testUsernameOnlyQueryCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE user_id = ".self::$userSubquery;
		$inputArray = ["username" => "clarkykestrel"];
		$result = SearchQueryBuilder::buildQuery($inputArray, true);
		$expectedParams = ["clarkykestrel"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testUsernameOnlyQueryInAdditionalUsersQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky"];
		$expectedParams = ["lavamunky"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testUsernameOnlyQueryInAdditionalUsersCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE user_id = ".self::$userSubquery;
		$inputArray = ["username" => "lavamunky"];
		$expectedParams = ["lavamunky"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testTextAndUsernameQueryQueryBuild() {
		$expectedQuery = self::$defaultSelect.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." AND ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "crittweets", "text" => "homeland"];
		$expectedParams = ["homeland", "crittweets", "homeland"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testTextAndUsernameQueryCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE user_id = ".self::$userSubquery." AND ".self::$textMatch;
		$inputArray = ["username" => "crittweets", "text" => "homeland"];
		$expectedParams = ["crittweets", "homeland"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testTextAndUsernameQueryInAdditionalUsersQueryBuild() {
		$expectedQuery = self::$defaultSelect.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." AND ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky", "text" => "ncis"];
		$expectedParams = ["ncis", "lavamunky", "ncis"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testTextAndUsernameQueryInAdditionalUsersCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE user_id = ".self::$userSubquery." AND ".self::$textMatch;
		$inputArray = ["username" => "lavamunky", "text" => "ncis"];
		$expectedParams = ["lavamunky", "ncis"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testTextAndUsernameWithRetweetsQueryQueryBuild() {
		$expectedQuery = self::$defaultSelect.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "fuckyeahlost", "text" => "dharma", "retweets" => "on"];
		$expectedParams = ["dharma", "fuckyeahlost", "fuckyeahlost", "dharma"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testTextAndUsernameWithRetweetsQueryCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND ".self::$textMatch;
		$inputArray = ["username" => "fuckyeahlost", "text" => "we have to go back", "retweets" => "on"];
		$expectedParams = ["fuckyeahlost", "fuckyeahlost", "we have to go back"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testTextAndUsernameInAdditionalUsersWithRetweetsQueryQueryBuild() {
		$expectedQuery = self::$defaultSelect.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND ".self::$textMatch." ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["username" => "lavamunky", "text" => "linux", "retweets" => "on"];
		$expectedParams = ["linux", "lavamunky", "lavamunky", "linux"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testTextAndUsernameInAdditionalUsersWithRetweetsQueryCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE (user_id = ".self::$userSubquery." OR retweeted_by_user_id = ".self::$userSubquery.") AND ".self::$textMatch;
		$inputArray = ["username" => "lavamunky", "text" => "twitter", "retweets" => "on"];
		$expectedParams = ["lavamunky", "lavamunky", "twitter"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testAtMentionsQueryWithMentionsDisabledQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "@me"];
		$expectedParams = ["@me"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testAtMentionsQueryWithMentionsDisabledCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE user_id = ".self::$userSubquery;
		$inputArray = ["username" => "@me"];
		$expectedParams = ["@me"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testAtMentionsQueryWithMentionsEnabledQueryBuild() {
		define('MENTIONS_TIMELINE', true);
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE user_id = ".self::$userSubquery." ORDER BY id DESC LIMIT 40";
		$inputArray = ["username" => "@me"];
		$expectedParams = ["@me"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testAtMentionsQueryWithMentionsEnabledCountQueryBuild() {
		$expectedQuery = self::$countSelect." WHERE user_id = ".self::$userSubquery;
		$inputArray = ["username" => "@me"];
		$expectedParams = ["@me"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray, true);
	}

	public function testExcludeRepliesQueryBuild() {
		$expectedQuery = self::$defaultSelect." FROM tweet NATURAL JOIN user WHERE text NOT LIKE '@%' ORDER BY id DESC LIMIT 40";
		$inputArray = ["exclude_replies" => "on"];
		$expectedParams = [];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}

	public function testExcludeRepliesWithTextSearchQueryBuild() {
		$expectedQuery = self::$defaultSelect.", ".self::$textMatch." as relevance FROM tweet NATURAL JOIN user WHERE ".self::$textMatch." AND text NOT LIKE '@%' ORDER BY relevance DESC, id DESC LIMIT 40";
		$inputArray = ["text" => "Fringe", "exclude_replies" => "on"];
		$expectedParams = ["Fringe", "Fringe"];
		$this->assertQueryPermutations($expectedQuery, $expectedParams, $inputArray);
	}
}

?>