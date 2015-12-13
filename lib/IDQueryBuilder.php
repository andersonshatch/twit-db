<?php

require_once dirname(__FILE__).'/QueryUtils.php';

class IDQueryBuilder {

	/**
	 * Build query for looking up tweets by ID
	 * @param array $array either array with key "ids" => csv_list_of_IDs or array of IDs
	 * @return array query string with parameter placeholders and query parameters for binding
	 */
	static function buildQuery(array $array) {
		$ids = array_key_exists('ids', $array) ? explode(',', $array['ids']) : $array;
		$queryParameters = $ids;
		$queryString = 'SELECT '.QueryUtils::QUERY_FIELDS.' FROM tweet NATURAL JOIN user WHERE id IN '.QueryUtils::parameterPlaceholderString(count($ids));

		return [$queryString, $queryParameters];
	}

}