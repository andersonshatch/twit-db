<?php

require_once 'QueryHolder.php';

class FavoriteUtil {
	private static $insertQuery;
	private static $mysqli;

	/**
	 * Record id of a tweet which was favorited
	 *
	 * @param $tweet tweet which was favorited
	 * @param mysqli $mysqli database handle
	 */
	public static function storeFavorite($tweet, mysqli $mysqli) {
		if(!self::$insertQuery || self::$mysqli !== $mysqli) {
			self::$insertQuery = QueryHolder::prepareAndHoldQuery($mysqli, "
				INSERT INTO favorite(id)
				VALUES (?)
			");
			self::$mysqli = $mysqli;
		}

		self::$insertQuery->bind_param('s', $tweet->id_str);
		self::$insertQuery->execute();
	}

}