<?php

require_once 'QueryHolder.php';
require_once 'QueryUtils.php';

class HashtagUtil {
	private static $insertQuery;
	private static $mysqli;

	/**
	 * Record sighting of a hashtag
	 *
	 * @param $hashtag hashtag entity
	 * @param $seenAtDate tweet created_at date
	 * @param mysqli $mysqli database handle
	 */
	public static function storeHashtag($hashtag, $seenAtDate, mysqli $mysqli) {
		if(!self::$insertQuery || self::$mysqli !== $mysqli) {
			self::$insertQuery = QueryHolder::prepareAndHoldQuery($mysqli, "
				INSERT INTO hashtag(name, first_seen, last_seen)
				VALUES(?, ?, ?)
				ON DUPLICATE KEY UPDATE
				name = ?,
				last_seen = ?,
				usage_count = usage_count + 1
			");
			self::$mysqli = $mysqli;
		}

		QueryUtils::bindQueryWithParams(self::$insertQuery, [$hashtag->text, $seenAtDate, $seenAtDate, $hashtag->text, $seenAtDate]);
		self::$insertQuery->execute();
	}

	/**
	 * Search for a hashtag by name
	 *
	 * @param $term to search for (wildcard will be added to end)
	 * @param mysqli $mysqli database handle
	 * @return array array of results
	 */
	public static function search($term, mysqli $mysqli) {
		self::$mysqli = $mysqli;
		$sql = "SELECT name, first_seen AS firstSeen, last_seen AS lastSeen, usage_count AS usageCount
				  FROM hashtag
				  WHERE name LIKE ?
				  ORDER BY usage_count DESC, last_seen DESC
				  LIMIT 8";

		$query = QueryHolder::prepareAndHoldQuery($mysqli, $sql);
		QueryUtils::bindQueryWithParams($query, [$term.'%']);
		$query->execute();

		return $query->get_result()->fetch_all(MYSQLI_ASSOC);
	}

	/**
	 * Populate the hashtag table with values from any existing tweets
	 *
	 * @param mysqli $mysqli database handle
	 */
	public static function populateHashtagTable(mysqli $mysqli) {
		self::$mysqli = $mysqli;

		$hashtagTweetsQuery = QueryHolder::prepareAndHoldQuery($mysqli, "
			SELECT id, text, entities_json, created_at
			FROM tweet
			WHERE text LIKE '%#%'
			AND entities_json IS NOT NULL
			AND id > ?
			ORDER BY id LIMIT 5000");

		$id = 0;

		while(true) {
			QueryUtils::bindQueryWithParams($hashtagTweetsQuery, [$id]);
			$hashtagTweetsQuery->execute();
			$chunk = $hashtagTweetsQuery->get_result()->fetch_all(MYSQLI_ASSOC);

			$count = count($chunk);
			if($count == 0) {
				break;
			}

			foreach($chunk as $tweet) {
				$entities = json_decode($tweet['entities_json']);
				$hashtags = $entities->hashtags;
				foreach($hashtags as $hashtag) {
					self::storeHashtag($hashtag, $tweet['created_at'], $mysqli);
				}
			}
			$id = $chunk[$count - 1]['id'];
		}
	}

}