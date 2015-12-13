<?php

chdir(dirname(__FILE__));
require_once 'AdditionalUserTest.php';
require_once 'IDQueryBuilderTest.php';
require_once 'LinkifyTest.php';
require_once 'SearchQueryBuilderTest.php';

class AllTests extends PHPUnit_Framework_TestCase {
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('twit-db');

		$suite->addTestSuite('AdditionalUserTest');
		$suite->addTestSuite('IDQueryBuilderTest');
		$suite->addTestSuite('LinkifyTest');
		$suite->addTestSuite('SearchQueryBuilderTest');

		return $suite;
	}

}

?>
