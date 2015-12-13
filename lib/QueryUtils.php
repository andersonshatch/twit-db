<?php

class QueryUtils {
	const QUERY_FIELDS = 'id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url';

	/**
	 * Bind parameters to a mysqli query
	 *
	 * Binds all parameters with 's' type -- string
	 * @param mysqli_stmt $query query to prepare
	 * @param array $params array of parameters to bind
	 */
	public static function bindQueryWithParams(mysqli_stmt $query, array $params) {
		if(!empty($params)) {
			$types = str_repeat('s', count($params));
			$paramRefs = [$types];

			foreach($params as &$param) {
				$paramRefs[] = &$param;
			}

			call_user_func_array([$query, 'bind_param'], $paramRefs);
			//When supporting only PHP 5.6 and above, replace above with below:
			//$query->bind_param(str_repeat('s', count($params)), ...$params);
		}
	}

	/**
	 * Generate a parameter placeholder string for use in IN queries. E.g. (?, ?, ?)
	 * @param $numberOfPlaceholders required number of ? placeholders
	 * @return string placeholder string with $numberOfPlaceholders
	 */
	public static function parameterPlaceholderString($numberOfPlaceholders) {
		$str = str_repeat('?, ', $numberOfPlaceholders);
		return "(".substr($str, 0, -2).")";
	}
}
