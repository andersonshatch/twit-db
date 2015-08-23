<?php

require_once dirname(__file__).'/HashtagUtil.php';
require_once dirname(__file__).'/QueryHolder.php';

function storeTweet($tweet, $mysqli) {
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
				entities_json
			) VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	$statement = QueryHolder::prepareAndHoldQuery($mysqli, $insertSQL);
	$retweetedBy = NULL;
	$retweetedById = NULL;
	$id = $tweet->id_str; //store id of status, not the retweeted_status.
	if(property_exists($tweet, 'retweeted_status')) {
		//if tweet is a retweet,
		//store the retweeter's information, then replace $tweet with the retweeted_status
		addOrUpdateUser($tweet->user, $mysqli);
		//set retweet fields
		$retweetedBy = $tweet->user->screen_name;
		$retweetedByID = $tweet->user->id_str;
		//replace the tweet with the retweeted_status, and store (mostly) that.
		$tweet = $tweet->retweeted_status;
	}
	if(property_exists($tweet, 'quoted_status')) {
		storeTweet($tweet->quoted_status, $mysqli);
	}

	//store the author information
	addOrUpdateUser($tweet->user, $mysqli);
	$createdat = new DateTime($tweet->created_at);
	$createdat = $createdat->format('Y-m-d H:i:s');

	$entities = $tweet->entities;

	if(property_exists($tweet, 'extended_entities')) {
		$entities = array_merge((array)$entities, (array)$tweet->extended_entities);
	}
	$entities = json_encode($entities);

	$statement->bind_param('sssssssssss',
		$id,
		$createdat,
		$tweet->source,
		$tweet->in_reply_to_status_id_str,
		$tweet->text,
		$retweetedBy,
		$retweetedByID,
		$tweet->place->full_name,
		$tweet->place->url,
		$tweet->user->id_str,
		$entities
	);
	$statement->execute();

	$addedRow = $mysqli->affected_rows == 1;

	//if tweet was new to the DB, process the hashtags it contained
	if($addedRow) {
		foreach($tweet->entities->hashtags as $hashtag) {
			HashtagUtil::storeHashtag($hashtag, $createdat, $mysqli);
		}
	}

	return $addedRow;
}

function addOrUpdateUser($user, $mysqli) {
	$statement = QueryHolder::prepareAndHoldQuery($mysqli, "
		INSERT into user (
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
		protected = VALUES(protected)"
	);

	$createdAt = null;
	if (property_exists($user, "created_at")) {
		$createdAt = new DateTime($user->created_at);
		$createdAt = $createdAt->format('Y-m-d H:i:s');
	}
	$statement->bind_param('sssssssssssss',
		$user->id_str,
		$user->description,
		$user->location,
		$user->screen_name,
		$user->name,
		$user->followers_count,
		$user->friends_count,
		$user->statuses_count,
		$user->url,
		$user->profile_image_url_https,
		$createdAt,
		$user->verified,
		$user->protected
	);
	$statement->execute();
	
}
?>
