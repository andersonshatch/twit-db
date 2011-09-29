<?php

function createTimelineTable($mysqli, $tableName){
	$create = $mysqli->query("
			CREATE TABLE `$tableName` (
				`id` bigint(30) unsigned NOT NULL DEFAULT '0',
				`created_at` datetime NOT NULL,
				`source` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
				`in_reply_to_status_id` bigint(30) unsigned DEFAULT NULL,
				`text` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
				`retweeted_by_screen_name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
				`retweeted_by_user_id` bigint(30) unsigned DEFAULT NULL,
				`place_full_name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
				`place_url` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
				`user_id` bigint(30) unsigned NOT NULL,
				`entities_json` mediumtext CHARACTER SET utf8,
				PRIMARY KEY (`id`),
				KEY `user_id_index` (`user_id`),
				FULLTEXT KEY `index` (`text`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
	);
	if( $create ){
		 createUserTimeline($mysqli);
		return true;
	}else{
		return false;
	}

}

function createUserTimeline($mysqli){
	$create = $mysqli->query("
			CREATE TABLE IF NOT EXISTS `users` (
				`screen_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
				`user_id` bigint(20) unsigned NOT NULL,
				`description` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
				`location` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
				`name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
				`followers_count` int(10) DEFAULT NULL,
				`friends_count` int(10) DEFAULT NULL,
				`statuses_count` int(10) DEFAULT NULL,
				`url` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
				`profile_image_url` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
				`user_created_at` datetime DEFAULT NULL,
				`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				`verified` tinyint(1) NOT NULL DEFAULT '0',
				`protected` tinyint(1) NOT NULL DEFAULT '0',
				 PRIMARY KEY (`user_id`),
				 FULLTEXT KEY `index` (`screen_name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
	);
	if( $create ){
		return true;
	}else{
		return false;
	}
}
