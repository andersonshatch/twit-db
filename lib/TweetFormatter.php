<?php

chdir(dirname(__FILE__));
require_once('linkify_tweet.php');

class TweetFormatter {
	private static $localDateTimeZone;
	private static $utcDateTimeZone;

	public static function formatTweets(&$tweets, $linkify = true) {
		foreach($tweets as &$tweet) {
			$tweet = TweetFormatter::formatTweet($tweet, $linkify);
		}

		return $tweets;
	}

	public static function formatTweet(&$tweet, $linkify = true) {
		if(self::$localDateTimeZone == null || self::$utcDateTimeZone == null) {
			self::$localDateTimeZone = new DateTimeZone(date_default_timezone_get());
			self::$utcDateTimeZone = new DateTimeZone('utc');
		}

		$tweet['id'] = (string) $tweet['id'];
		$tweet['user_id'] = (string) $tweet['user_id'];
		if($tweet['retweeted_by_user_id'] !== null) {
			$tweet['retweeted_by_user_id'] = (string) $tweet['retweeted_by_user_id'];
		}

		$createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $tweet['created_at'], self::$utcDateTimeZone);
		$createdAt->setTimezone(self::$localDateTimeZone);
		$tweet['datetime'] = $createdAt->format('c');
		$tweet['timestamp_title'] = $createdAt->format('G:i M jS \'y');
		if($linkify) {
			$tweet['text'] = linkify_tweet($tweet['text'], $tweet['entities_json']);
		} else {
			$tweet['entities'] = json_decode($tweet['entities_json']);
		}

		unset($tweet['entities_json']);

		return $tweet;
	}
}

?>
