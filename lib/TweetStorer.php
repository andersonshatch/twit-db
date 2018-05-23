<?php

require_once dirname(__FILE__).'/HashtagUtil.php';
require_once dirname(__FILE__).'/QueryHolder.php';
require_once dirname(__FILE__).'/UserUtil.php';

class TweetStorer {
	/**
	 * Insert a tweet to the tweet table
	 * @param stdClass $tweet tweet object from twitter API
	 * @param mysqli $mysqli database handle
	 * @return bool true if tweet's ID was new to the database
	 */
	public static function storeTweet(stdClass $tweet, mysqli $mysqli) {
		$insertSQL = "
			INSERT IGNORE INTO tweet (
				id,
				created_at,
				source,
				in_reply_to_status_id,
				text,
				retweeted_by_screen_name,
				retweeted_by_user_id,
				place_full_name,
				place_url,
				user_id,
				entities_json,
				display_range_start,
				display_range_end
			) VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$statement = QueryHolder::prepareAndHoldQuery($mysqli, $insertSQL);
		$retweetedBy = NULL;
		$retweetedById = NULL;
		$id = $tweet->id_str; //store id of status, not the retweeted_status.
		if(property_exists($tweet, 'retweeted_status')) {
			//if tweet is a retweet,
			//store the retweeter's information, then replace $tweet with the retweeted_status
			UserUtil::upsertUser($tweet->user, $mysqli);
			//set retweet fields
			$retweetedBy = $tweet->user->screen_name;
			$retweetedByID = $tweet->user->id_str;
			//replace the tweet with the retweeted_status, and store (mostly) that.
			$tweet = (object)$tweet->retweeted_status;
			$tweet->user = (object)$tweet->user;
		}
		if(property_exists($tweet, 'quoted_status')) {
			$tweet->quoted_status = (object)$tweet->quoted_status;
			$tweet->quoted_status->user = (object)$tweet->quoted_status->user;
			TweetStorer::storeTweet($tweet->quoted_status, $mysqli);
		}

		//store the author information
		UserUtil::upsertUser($tweet->user, $mysqli);
		$createdat = new DateTime($tweet->created_at);
		$createdat = $createdat->format('Y-m-d H:i:s');

		$entities = $tweet->entities;

		if(property_exists($tweet, 'extended_entities')) {
			$entities = array_merge((array)$entities, (array)$tweet->extended_entities);
		}
		if(property_exists($tweet, 'quoted_status_id_str') && $tweet->quoted_status_id_str !== null) {
			$entitiesObj = (object)$entities;
			$entitiesObj->quotedStatusId = $tweet->quoted_status_id_str;
		}
		$entities = json_encode($entities);
		$text = property_exists($tweet, "full_text") ? $tweet->full_text : $tweet->text;

		$tweet->place = (object)$tweet->place;
		$displayRange = property_exists($tweet, "display_text_range") ? $tweet->display_text_range : null;
		$displayRangeStart = $displayRange === null ? null : $displayRange[0];
		$displayRangeEnd = $displayRange === null ? null : $displayRange[1];

		$statement->bind_param('sssssssssssss',
			$id,
			$createdat,
			$tweet->source,
			$tweet->in_reply_to_status_id_str,
			$text,
			$retweetedBy,
			$retweetedByID,
			$tweet->place->full_name,
			$tweet->place->url,
			$tweet->user->id_str,
			$entities,
			$displayRangeStart,
			$displayRangeEnd
		);
		$statement->execute();

		$addedRow = $mysqli->affected_rows == 1;

		//if tweet was new to the DB, process the hashtags it contained
		if($addedRow) {
			$entities = (object)$tweet->entities;
			$hashtags = (object)$entities->hashtags;
			foreach($hashtags as $hashtag) {
				HashtagUtil::storeHashtag((object)$hashtag, $createdat, $mysqli);
			}
		}

		return $addedRow;
	}
}

?>
