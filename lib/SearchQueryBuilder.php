<?php

class SearchQueryBuilder {
	static function buildQuery(array $array, $count = false, $sortAscending = false) {
		$queryParameters = [];
		$queryString = 'SELECT ';
		if($count) {
			$queryString .= 'COUNT(1)';
		} else {
			$queryString .= 'id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url';
		}

		$textCondition = "";
		if(self::existsAndNotBlank('text', $array)) {
			$textCondition = "MATCH(`text`) AGAINST(? IN BOOLEAN MODE)";
			if(!$count) {
				$queryString .= ", $textCondition as relevance";
				$queryParameters[] = $array['text'];
			}
		}

		$queryString .= $count ? ' FROM tweet' : ' FROM tweet NATURAL JOIN user';

		if(self::existsAndNotBlank('favorites_only', $array) && $array['favorites_only'] == 'on') {
			$queryString .= ' NATURAL JOIN favorite';
		}

		$conditionals = [];

		if(!empty($array)) {
			if(self::existsAndNotBlank('username', $array)) {
				$uIdQueryString = "(SELECT user_id FROM user WHERE MATCH(`screen_name`) AGAINST(?))";
				$username = strtolower($array['username']);
				$queryParameters[] = $username;
				if(array_key_exists('retweets', $array) && $array['retweets'] == 'on') {
					$conditionals[] = "(user_id = $uIdQueryString OR retweeted_by_user_id = $uIdQueryString)";
					$queryParameters[] = $username;
				} else {
					$conditionals[] = "user_id = $uIdQueryString";
				}
			}
			if($textCondition) {
				$conditionals[] = $textCondition;
				$queryParameters[] = $array['text'];
			}

			static $paginationParams = ['since_id' => '>', 'max_id' => '<'];
			foreach($paginationParams as $paginationParam => $operator) {
				if(!self::existsAndNotBlank($paginationParam, $array)) {
					continue;
				}

				if($textCondition && self::existsAndNotBlank('relevance', $array)) {
					$conditionals[] = "(($textCondition = ? AND id $operator ?) OR $textCondition $operator ?)";
					$queryParameters[] = $array['text']; //param in $textCondition
					$queryParameters[] = $array['relevance'];
					$queryParameters[] = $array[$paginationParam];
					$queryParameters[] = $array['text']; //param in $textCondition
					$queryParameters[] = $array['relevance'];
				} else {
					$conditionals[] = "id $operator ?";
					$queryParameters[] = $array[$paginationParam];
				}
			}
		}

		if(array_key_exists('exclude_replies', $array) && $array['exclude_replies'] == 'on') {
			$conditionals[] = "text NOT LIKE '@%'";
		}

		if(!empty($conditionals)) {
			$queryString .= ' WHERE '.implode(' AND ', $conditionals);
		}

		if(!$count) {
			$sort = $sortAscending ? 'ASC' : 'DESC';
			if($textCondition) {
				$queryString .= " ORDER BY relevance $sort, id $sort LIMIT 40";
			} else {
				$queryString .= " ORDER BY id $sort LIMIT 40";
			}
		}

		return [$queryString, $queryParameters];
	}

	public static function prepareQuery(mysqli_stmt $query, array $params) {
		if(!empty($params)) {
			$query->bind_param(str_repeat('s', count($params)), ...$params);
		}
	}

    private static function existsAndNotBlank($key, $array) {
        if(!array_key_exists($key, $array) || $array[$key] == '') {
            return false;
        }
        return true;
    }
}

?>
