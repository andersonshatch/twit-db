<?php
function buildQuery($array, $mysqli){
	$table = 'home';
	if( !empty($array) ){
		if(array_key_exists("username", $array)){
			require_once 'additional_users.php';
			$userTables = create_users_array(ADDITIONAL_USERS);
			if(in_array($_POST['username'], $userTables)){
				$table = "@{$_POST['username']}";
			}
			if( defined("MENTIONS_TIMELINE") && MENTIONS_TIMELINE == "true" && $_POST['username'] == '@me' ){
				$table = "mentions";
			}
		}
	}
	$queryString = "SELECT * from `$table` NATURAL JOIN `users`";
	$and = false;

	if( !empty($array) ){
		if( array_key_exists("username", $array) && $array['username'] != '' && ($array['username'] != '@me' || !defined("MENTIONS_TIMELINE")) ){
			$uIDQuery = $mysqli->query("SET @uID = (SELECT user_id FROM users WHERE MATCH(`screen_name`) AGAINST('".$mysqli->real_escape_string($array['username'])."'))");
			$GLOBALS['queryCount']++;
			if( array_key_exists("retweets", $array) && $array['retweets'] = 'on' ){
				(!$and) ? $queryString.=" WHERE (user_id = @uID OR retweeted_by_user_id = @uID)" : $queryString.=" AND (user_id = @uID OR retweeted_by_user_id = @uID)";
			}else{
				(!$and) ? $queryString.=" WHERE user_id = @uID" : $queryString.=" AND user_id = @uID";
			}
			$and = true;
		}
		if( array_key_exists("text", $array) && $array['text'] != '' ){
			$text = $array['text'];
			(!$and) ? $queryString.=" WHERE MATCH(`text`) AGAINST('".mysqli_real_escape_string($mysqli, $text)."' IN BOOLEAN MODE)" : $queryString.=" AND MATCH(`text`) AGAINST('".mysqli_real_escape_string($mysqli, $text)."' IN BOOLEAN MODE)";
			$and = true;
		}

		$queryString .= " ORDER BY id DESC";

		if( array_key_exists("limit", $array) && $array['limit'] != '' ){
			$queryString.=" LIMIT ".mysqli_real_escape_string($mysqli, $array['limit']);
		}
	}
	else{
		$queryString.=" ORDER BY id DESC LIMIT 20";
	}
	return $queryString;
}
?>
