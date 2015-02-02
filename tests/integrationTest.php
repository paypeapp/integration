<?php
class IntegrationTest extends PHPUnit_Framework_TestCase
{
	public function testPersonalCodeValidation()
	{
		require_once(dirname(__FILE__) . '/../library.php');

		$this->assertFalse(Library::validateEstonianPersonalCode('123456789'));
		$this->assertTrue(Library::validateEstonianPersonalCode(49403136515));
		$this->assertFalse(Library::validateEstonianPersonalCode(39710290226));
	}
}
