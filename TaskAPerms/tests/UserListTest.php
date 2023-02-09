<?php

use PHPUnit\Framework\TestCase;

require __DIR__ . "/../src/UserList.php";

class UserListTest extends TestCase {
	protected $ul;

	/** Runs before every test method */
	function setUp(): void {
		$this->ul = new UserList();
	}

	function test_linkMainAndAltUsernames_basic() {
		// data for this test
		$this->ul->data = [
			'sysop' => [
				'AdminUser' => 1,
			],
			'extendedconfirmed' => [
				'RegularUser' => 1,
			],
		];
		$this->ul->linkedUsernames['AltAccount'] = 'AdminUser';

		// test
		$this->ul->linkMainAndAltUsernames();
		$actual = $this->ul->data;
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