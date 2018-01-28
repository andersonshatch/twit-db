<?php

chdir(dirname(__FILE__));
require_once 'AdditionalUserTest.php';
require_once 'IDQueryBuilderTest.php';
require_once 'LinkifyTest.php';
require_once 'SearchQueryBuilderTest.php';

class AllTests extends \PHPUnit\Framework\TestCase {
	public static function suite() {
		$suite = new \PHPUnit\Framework\TestSuite('twit-db');

		$suite->addTestSuite('AdditionalUserTest');
		$suite->addTestSuite('IDQueryBuilderTest');
		$suite->addTestSuite('LinkifyTest');
		$suite->addTestSuite('SearchQueryBuilderTest');

		return $suite;
	}

}

?>
