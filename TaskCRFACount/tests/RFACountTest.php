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
		$string = file_get_contents(__DIR__ . '/RFACountTest.json');
		$return = json_decode($string, true);
		return $return;
	}
	/**
	  * @dataProvider provideCountRFBData
	  */
	function test_countRFBs($testName, $wikicode, $expected) {
		$result = countRFBs($wikicode);
		$this->assertSame($expected, $result, $testName);
	}

	function provideCountRFBData() {
		$string = file_get_contents(__DIR__ . '/RFBCountTest.json');
		$return = json_decode($string, true);
		return $return;
	}}