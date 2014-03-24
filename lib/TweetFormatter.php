<?php

chdir(dirname(__FILE__));
require_once('linkify_tweet.php');

class TweetFormatter {

	public static function formatTweets(&$tweets, $linkify = true) {
		foreach($tweets as &$tweet) {
			$tweet = TweetFormatter::formatTweet($tweet, $linkify);
		}

		return $tweets;
	}

	public static function formatTweet(&$tweet, $linkify = true) {
		$createdAt = date_create_from_format('Y-m-d H:i:s', $tweet['created_at']);
		$tweet['datetime'] = $createdAt->format('c');
		$tweet['timestamp_title'] = $createdAt->format('G:i M jS \'y');
		if($linkify) {
			$tweet['text'] = linkify_tweet($tweet['text'], $tweet['entities_json']);
			unset($tweet['entities_json']);
		} else {
			$tweet['entities'] = json_decode($tweet['entities_json']);
			unset($tweet['entities_json']);
		}

		return $tweet;
	}
}

?>
