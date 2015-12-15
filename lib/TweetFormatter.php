<?php

chdir(dirname(__FILE__));
require_once('linkify_tweet.php');
require_once('model/Tweet.php');

class TweetFormatter {
	private static $localDateTimeZone;
	private static $utcDateTimeZone;

	public static function formatTweets(array $tweets, $linkify = true) {
		$formattedTweets = [];
		foreach($tweets as $tweetDictionary) {
			$tweet = TweetFormatter::formatTweet($tweetDictionary, $linkify);
			$formattedTweets[] = $tweet;
		}

		return $formattedTweets;
	}

	public static function formatTweet(array $tweetDictionary, $linkify = true) {
		if(self::$localDateTimeZone == null || self::$utcDateTimeZone == null) {
			self::$localDateTimeZone = new DateTimeZone(date_default_timezone_get());
			self::$utcDateTimeZone = new DateTimeZone('utc');
		}

		$tweetDictionary['id'] = (string) $tweetDictionary['id'];
		$tweetDictionary['user_id'] = (string) $tweetDictionary['user_id'];
		if($tweetDictionary['retweeted_by_user_id'] !== null) {
			$tweetDictionary['retweeted_by_user_id'] = (string) $tweetDictionary['retweeted_by_user_id'];
		}

		$createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $tweetDictionary['created_at'], self::$utcDateTimeZone);
		$createdAt->setTimezone(self::$localDateTimeZone);
		$dateTime = $createdAt->format('c');
		$timestampTitle = $createdAt->format('G:i M jS \'y');

		$entities = json_decode($tweetDictionary['entities_json']);
		unset($tweetDictionary['entities_json']);

		$tweet = new \TwitDB\Model\Tweet($tweetDictionary);

		if($linkify) {
			$tweet->setText(linkify_tweet($tweetDictionary['text'], $entities));
		} else {
			$tweet->setEntities($entities);
		}

		$tweet->setDateTime($dateTime);
		$tweet->setTimestampTitle($timestampTitle);

		return $tweet;
	}
}

?>
