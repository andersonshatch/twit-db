<?php

chdir(dirname(__FILE__));
require_once('IDQueryBuilder.php');
require_once('linkify_tweet.php');
require_once('model/Tweet.php');
require_once('QueryHolder.php');
require_once('QueryUtils.php');

class TweetFormatter {
	private static $localDateTimeZone;
	private static $utcDateTimeZone;

	public static function formatTweets(array $tweets, $linkify = true, mysqli $mysqli = null) {
		$formattedTweets = [];
		foreach($tweets as $tweetDictionary) {
			$tweet = TweetFormatter::formatTweet($tweetDictionary, $linkify, $mysqli);
			$formattedTweets[] = $tweet;
		}

		return $formattedTweets;
	}

	public static function formatTweet(array $tweetDictionary, $linkify = true, mysqli $mysqli = null) {
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

		if(!array_key_exists('entities', $tweetDictionary) && array_key_exists('entities_json', $tweetDictionary)) {
			$tweetDictionary['entities'] = array_key_exists('entities_json', $tweetDictionary) ?  json_decode($tweetDictionary['entities_json']) : null;
		}
		unset($tweetDictionary['entities_json']);

		$quotedTweetModel = null;
		if($mysqli !== null) {
			$quotedTweet = TweetFormatter::lookupQuotedTweet($tweetDictionary, $mysqli);
			if($quotedTweet !== null) {
				$quotedTweetModel = TweetFormatter::formatTweet($quotedTweet, $linkify);
			}
		}

		$tweet = new \TwitDB\Model\Tweet($tweetDictionary);
		$tweet->setQuotedTweet($quotedTweetModel);

		if($linkify) {
			$tweet->setText(linkify_tweet($tweetDictionary['text'], $tweetDictionary['entities']));
		} else {
			$tweet->setEntities($tweetDictionary['entities']);
		}

		$tweet->setDateTime($dateTime);
		$tweet->setTimestampTitle($timestampTitle);

		return $tweet;
	}

	private static function lookupQuotedTweet(array $tweetDictionary, mysqli $mysqli) {
		$urls = $tweetDictionary['entities']->urls;

		//iterate through the links backwards and pick the last matching quote link
		//this matches behaviour observed by the api results
		$urls = array_reverse($urls);

		$id = null;

		foreach($urls as $url) {
			$expandedUrl = $url->expanded_url;
			if(preg_match('/https:\/\/twitter.com\/.*\/status\/[0-9]+/', $expandedUrl)) {
				$linkComponents = explode('/', $expandedUrl);
				$id = end($linkComponents);
				break;
			}
		}

		if($id === null) {
			return null;
		}

		list($queryString, $queryParams) = IDQueryBuilder::buildQuery([$id]);

		$query = QueryHolder::prepareAndHoldQuery($mysqli, $queryString);
		QueryUtils::bindQueryWithParams($query, $queryParams);
		$query->execute();

		$result = $query->get_result();
		$results = $result->fetch_assoc();

		return $results;
	}
}

?>
