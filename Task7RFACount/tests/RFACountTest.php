<?php

use PHPUnit\Framework\TestCase;

include 'src/RFACount.php';

class RFACountTest extends TestCase {
	protected $rfaCount;

	public function setUp(): void {
		$this->rfaCount = new RFACount();
	}

	/**
	 * @dataProvider provideCountRFAData
	 */
	public function test_countRFAs( $testName, $wikicode, $expected ) {
		$result = $this->rfaCount->countRFAs( $wikicode );
		$this->assertSame( $expected, $result, $testName );
	}

	public function provideCountRFAData() {
		$string = file_get_contents( __DIR__ . '/RFACountTest.json' );
		$return = json_decode( $string, true );
		return $return;
	}

	/**
	 * @dataProvider provideCountRFBData
	 */
	public function test_countRFBs( $testName, $wikicode, $expected ) {
		$result = $this->rfaCount->countRFBs( $wikicode );
		$this->assertSame( $expected, $result, $testName );
	}

	public function provideCountRFBData() {
		$string = file_get_contents( __DIR__ . '/RFBCountTest.json' );
		$return = json_decode( $string, true );
		return $return;
	}
}
