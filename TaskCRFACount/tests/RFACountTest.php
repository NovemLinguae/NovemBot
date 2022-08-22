<?php

use PHPUnit\Framework\TestCase;
include 'src/RFACount.php';

class RFACountTest extends TestCase {
	/**
	  * @dataProvider provideCountRFAData
	  */
	function test_countRFAs($testName, $wikicode, $expected) {
		$result = countRFAs($wikicode);
		$this->assertSame($expected, $result, $testName);
	}

	function provideCountRFAData() {
		echo __DIR__ . '/tests/RFACountTest.json';
		$string = file_get_contents(__DIR__ . '/RFACountTest.json');
		$return = json_decode($string, true);
		return $return;
	}
}