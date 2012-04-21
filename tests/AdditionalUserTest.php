<?php

chdir(dirname(__FILE__));
require_once '../lib/additional_users.php';

class AdditionalUserTest extends PHPUnit_Framework_TestCase {
	
	public function testEmptyUserList() {
		$input = '';
		$uArray = create_users_array($input);
		$uString = create_users_string($uArray);

		$this->assertEmpty($uArray);
		$this->assertEquals('', $uString);
	}

	public function testSingleUser() {
		$input = 'Andersonshatch';
		$expected = 'andersonshatch';
		$uArray = create_users_array($input);
		$uString = create_users_string($uArray);

		$this->assertTrue(count($uArray) == 1);
		$this->assertEquals($expected, $uString);
	}

	public function testValidList() {
		$input = 'andersonshatch,babbanator,twitter';
		$uArray = create_users_array($input);
		$uString = create_users_string($uArray);

		$this->assertTrue(count($uArray) == 3);
		$this->assertEquals($input, $uString);
	}

	public function testSpaceRemoval() {
		$input = '  andersonshatch  , babbanator,   twitter       ';
		$expected = 'andersonshatch,babbanator,twitter';
		$uArray = create_users_array($input);
		$uString = create_users_string($uArray);

		$this->assertTrue(count($uArray) == 3);
		$this->assertEquals($expected, $uString);
	}

	public function testDuplicateRemoval() {
		$input = 'andersonshatch, andersonshatch, twitter, andersonshatch';
		$expected = 'andersonshatch,twitter';
		$uArray = create_users_array($input);
		$uString = create_users_string($uArray);

		$this->assertTrue(count($uArray) == 2);
		$this->assertEquals($expected, $uString);
	}

	public function testEmptyRemoval() {
		$input = ',andersonshatch,,babbanator, ,,';
		$expected = 'andersonshatch,babbanator';
		$uArray = create_users_array($input);
		$uString = create_users_string($uArray);

		$this->assertTrue(count($uArray) == 2);
		$this->assertEquals($expected, $uString);
	}

	public function testAllValidationCombined() {
		$input = ' ,,andersonshatch   , andersonshatch, BABBanator,twitter';
		$expected = 'andersonshatch,babbanator,twitter';
		$uArray = create_users_array($input);
		$uString = create_users_string($uArray);

		$this->assertTrue(count($uArray) == 3);
		$this->assertEquals($expected, $uString);
	}
}

?>
