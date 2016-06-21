<?php

require_once dirname(__FILE__).'/../lib/IDQueryBuilder.php';


class IDQueryBuilderTest extends PHPUnit_Framework_TestCase {
	private static $select = 'SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url, display_range_start, display_range_end';

	public function testSingleIdSearch() {
		$expectedQuery = self::$select.' FROM tweet NATURAL JOIN user WHERE id IN (?)';
		$inputArray = ['ids' => '187996142913585152'];
		$expectedParams = [$inputArray['ids']];

		$result = IDQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals($expectedParams, $result[1]);

		$inputArray = explode(',', $inputArray['ids']);
		$result = IDQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals($expectedParams, $result[1]);
	}

	public function testMultipleIdSearch() {
		$expectedQuery = self::$select.' FROM tweet NATURAL JOIN user WHERE id IN (?, ?, ?, ?, ?)';
		$inputArray = ['ids' => '123456,789123,423532,631235,452361'];
		$expectedParams = ['123456', '789123', '423532', '631235', '452361'];

		$result = IDQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals($expectedParams, $result[1]);

		$inputArray = explode(',', $inputArray['ids']);
		$result = IDQueryBuilder::buildQuery($inputArray);
		$this->assertEquals($expectedQuery, $result[0]);
		$this->assertEquals($expectedParams, $result[1]);
	}
}
