<?php

use PHPUnit\Framework\TestCase;

class LinkedUsernamesTest extends TestCase {
	protected $linkedUsernames;
	protected $data;

	function linkMainAndAltUsernames() {
		foreach ( $this->linkedUsernames as $altUsername => $mainUsername ) {
			foreach ( $this->data as $permission => $arrayOfUsernames ) {
				if ( $this->data[$permission][$mainUsername] ?? '' ) {
					$this->data[$permission][$altUsername] = 1;
				}
			}
		}
	}

	function test_linkMainAndAltUsernames_basic() {
		// wipe old test data
		$this->data = [];
		$this->linkedUsernames = [];
		
		// data for this test
		$this->data = [
			'sysop' => [
				'AdminUser' => 1,
			],
			'extendedconfirmed' => [
				'RegularUser' => 1,
			],
		];
		$this->data['extendedconfirmed']['RegularUser'] = 1;
		$this->linkedUsernames['AltAccount'] = 'AdminUser';

		// test
		$this->linkMainAndAltUsernames();
		$actual = $this->data;
		$expected = [
			'sysop' => [
				'AdminUser' => 1,
				'AltAccount' => 1,
			],
			'extendedconfirmed' => [
				'RegularUser' => 1,
			],
		];
		$this->assertSame($expected, $actual);
	}
}