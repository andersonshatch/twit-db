<?php
function buildQuery($array, $mysqli, $count = false) {
	$table = 'home';
	if(existsAndNotBlank('username', $array)) {
		require_once 'additional_users.php';
		$userTables = defined('ADDITIONAL_USERS') ? create_users_array(ADDITIONAL_USERS) : array();
		if(in_array(strtolower($array['username']), $userTables)) {
			$table = "@".strtolower($array['username']);
		}
		if(defined('MENTIONS_TIMELINE') && MENTIONS_TIMELINE == "true" && $array['username'] == '@me') {
			$table = "mentions";
		}
	}
	$queryString = "SELECT ";
	if($count) {
		$queryString .= "COUNT(1)";
	} else {
		$queryString .= "id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url";
	}

	$textCondition = "";
	if(existsAndNotBlank('text', $array)) {
		$textCondition = "MATCH(`text`) AGAINST('".$mysqli->real_escape_string($array['text'])."' IN BOOLEAN MODE)";
		if(!$count) {
			$queryString .= ", $textCondition as relevance";
		}
	}

	$queryString .= " FROM `$table` NATURAL JOIN `users`";

	$conditionals = array();
	if(!empty($array)) {
		if(existsAndNotBlank('username', $array) && ($array['username'] != '@me' || !defined('MENTIONS_TIMELINE'))) {
			$uIdQueryString = "(SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('".$mysqli->real_escape_string(strtolower($array['username']))."'))";
			if(array_key_exists('retweets', $array) && $array['retweets'] == 'on') {
				$conditionals[] = "(user_id = $uIdQueryString OR retweeted_by_user_id = $uIdQueryString)";
			} else {
				$conditionals[] = "user_id = $uIdQueryString";
			}
		}
		if($textCondition) {
			$conditionals[] = $textCondition;
		}

		if(existsAndNotBlank('max_id', $array)) {
			if($textCondition && existsAndNotBlank('relevance', $array)) {
				$relevance = $mysqli->real_escape_string($array['relevance']);
				$conditionals[] = "(($textCondition = $relevance AND id < ".$mysqli->real_escape_string($array['max_id']).") OR $textCondition < $relevance)";
			} else {
				$conditionals[] = "id < ".$mysqli->real_escape_string($array['max_id']);
			}
		}
	}

	$keyword = " WHERE ";
	foreach($conditionals as $condition) {
		$queryString .= $keyword.array_shift($conditionals);
		$keyword = " AND ";
	}

	if(!$count) {
		if($textCondition) {
			$queryString .= " ORDER BY relevance DESC, id DESC LIMIT 40";		
		} else {
			$queryString .= " ORDER BY id DESC LIMIT 40";
		}
	}

	return $queryString;

}

function existsAndNotBlank($key, $array) {
	if(!array_key_exists($key, $array) || $array[$key] == '') {
		return false;
    }   
	return true;
}

?>
