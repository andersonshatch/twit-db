<?php

require_once 'QueryHolder.php';
require_once 'QueryUtils.php';

class UserUtil {
	private static $insertQuery;

	/**
	 * Upsert a twitter user to the user table
	 * @param stdClass $user user object from twitter API
	 * @param mysqli $mysqli database handle
	 */
	public static function upsertUser(stdClass $user, mysqli $mysqli) {
		if(!self::$insertQuery) {
			self::$insertQuery = QueryHolder::prepareAndHoldQuery($mysqli, "
			   INSERT INTO user (
				user_id,
				description,
				location,
				screen_name,
				name,
				followers_count,
				friends_count,
				statuses_count,
				url,
				profile_image_url,
				user_created_at,
				verified,
				protected
			   ) VALUES
			   (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			   ON DUPLICATE KEY UPDATE
			   description = VALUES(description),
			   location = VALUES(location),
			   screen_name = VALUES(screen_name),
			   name = VALUES(name),
			   followers_count = VALUES(followers_count),
			   friends_count = VALUES(friends_count),
			   statuses_count = VALUES(statuses_count),
			   url = VALUES(url),
			   profile_image_url = VALUES(profile_image_url),
			   user_created_at = VALUES(user_created_at),
			   verified = VALUES(verified),
			   protected = VALUES(protected)");
		}

		$createdAt = null;
		if(property_exists($user, 'created_at')) {
			$createdAt = new DateTime($user->created_at);
			$createdAt = $createdAt->format('Y-m-d H:i:s');
		}

		QueryUtils::bindQueryWithParams(self::$insertQuery, [
			&$user->id_str,
			&$user->description,
			&$user->location,
			&$user->screen_name,
			&$user->name,
			&$user->followers_count,
			&$user->friends_count,
			&$user->statuses_count,
			&$user->url,
			&$user->profile_image_url_https,
			&$createdAt,
			&$user->verified,
			&$user->protected
		]);

		self::$insertQuery->execute();
	}

	/**
	 * Search for a user by screename and full name
	 *
	 * @param $term to search for (wildcard will be added to end)
	 * @param mysqli $mysqli database handle
	 * @return array array of results
	 */
	public static function search($term, mysqli $mysqli) {
		$searchQuery = QueryHolder::prepareAndHoldQuery($mysqli, "
			SELECT screen_name AS screenName, name, profile_image_url AS imageUrl
			FROM user
			WHERE screen_name LIKE ?
			OR name LIKE ?
			LIMIT 8");

		$termWithWildcard = $term.'%';
		QueryUtils::bindQueryWithParams($searchQuery, [$termWithWildcard, $termWithWildcard]);
		$searchQuery->execute();

		return $searchQuery->get_result()->fetch_all(MYSQLI_ASSOC);
	}
}