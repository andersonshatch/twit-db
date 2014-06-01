<?php

function storeTweet($tweet, $tableName, $mysqli) {
	$insertSQL = "
			INSERT IGNORE INTO `$tableName`(
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
	if(!array_key_exists('tweetPreparedStatement', $GLOBALS) || $GLOBALS['tweetPreparedQueryString'] != $insertSQL) {
		//if tweetPreparedStatment isn't defined, or the query string has changed, create/update it.
		//else, prepared query gets reused.
		$GLOBALS['tweetPreparedStatement'] = $mysqli->prepare($insertSQL);	
		$GLOBALS['tweetPreparedQueryString'] = $insertSQL;
	}
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
	//store the author information
	addOrUpdateUser($tweet->user, $mysqli);
	$createdat = new DateTime($tweet->created_at);
	$createdat = $createdat->format('Y-m-d H:i:s');
	$entities = json_encode($tweet->entities);
	$GLOBALS['tweetPreparedStatement']->bind_param('sssssssssss',
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
	$GLOBALS['tweetPreparedStatement']->execute();

	return $mysqli->affected_rows == 1;
}

function addOrUpdateUser($user, $mysqli) {
	if(!array_key_exists('userPreparedStatement', $GLOBALS)) {
		$GLOBALS['userPreparedStatement'] = $mysqli->prepare("
			INSERT into `users`(
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
	}

	$createdAt = null;
	if (property_exists($user, "created_at")) {
		$createdAt = new DateTime($user->created_at);
		$createdAt = $createdAt->format('Y-m-d H:i:s');
	}
	$GLOBALS['userPreparedStatement']->bind_param('sssssssssssss',
		$user->id_str,
		$user->description,
		$user->location,
		$user->screen_name,
		$user->name,
		$user->followers_count,
		$user->friends_count,
		$user->statuses_count,
		$user->url,
		$user->profile_image_url,
		$createdAt,
		$user->verified,
		$user->protected
	);
	$GLOBALS['userPreparedStatement']->execute();
	
}
?>
