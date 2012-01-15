<?php
function buildQuery($array, $mysqli, $count = false){
	$table = 'home';
	if( !empty($array) ){
		if(array_key_exists('username', $array)){
			require_once 'additional_users.php';
			$userTables = create_users_array(ADDITIONAL_USERS);
			if(in_array($array['username'], $userTables)){
				$table = "@{$array['username']}";
			}
			if( defined('MENTIONS_TIMELINE') && MENTIONS_TIMELINE == "true" && $array['username'] == '@me' ){
				$table = "mentions";
			}
		}
	}
	$queryString = "SELECT ";
	if($count)
		$queryString .= "COUNT(1) ";
	else
		$queryString .= "id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url ";
	$queryString .= "FROM `$table` NATURAL JOIN `users`";

	$conditionals = array();
	if( !empty($array) ){
		if( array_key_exists('username', $array) && $array['username'] != '' && ($array['username'] != '@me' || !defined('MENTIONS_TIMELINE')) ){
			$uIdQuery = $mysqli->query("SET @uID = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('".$mysqli->real_escape_string($array['username'])."'))");
			if( array_key_exists('retweets', $array) && $array['retweets'] == 'on'){
				$conditionals[] = "(user_id = @uID OR retweeted_by_user_id = @uID)";
			}else{
				$conditionals[] = "user_id = @uID";
			}
		}
		if( array_key_exists('text', $array) && $array['text'] != '' ){
			$conditionals[] = "MATCH(`text`) AGAINST('".$mysqli->real_escape_string($array['text'])."' IN BOOLEAN MODE)";
		}
		if( array_key_exists('max_id', $array) && $array['max_id'] != '' ){
			$conditionals[] = "id < ".$mysqli->real_escape_string($array['max_id']);
		}
	}

	$keyword = " WHERE ";
	foreach($conditionals as $condition){
		$queryString .= $keyword.array_shift($conditionals);
		$keyword = " AND ";
	}

	if(!$count)
		$queryString .= " ORDER BY id DESC LIMIT 40";

	return $queryString;

}
?>
