<?php

use PHPUnit\Framework\TestCase;

require __DIR__ . "/../src/UserList.php";
require __DIR__ . "/../src/HardCodedSocks.php";

class UserListTest extends TestCase {
	protected $ul;

	/** Runs before every test method */
	public function setUp(): void {
		$this->ul = new UserList();
	}

	public function test_linkMainAndAltUsernames_basic() {
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

	public function test_linkMainAndAltUsernames_multiplePerms() {
		// data for this test
		$this->ul->data = [
			"sysop" => [
				"Admin" => 1,
			],
			"formeradmin" => [
				"Admin" => 1,
			],
			"10k" => [
				"Admin AWB Account" => 1,
				"Admin" => 1,
			],
			"extendedconfirmed" => [
				"Admin AWB Account" => 1,
			],
		];
		$this->ul->linkedUsernames['Admin AWB Account'] = 'Admin';

		// test
		$this->ul->linkMainAndAltUsernames();
		$actual = $this->ul->data;
		$expected = [
			"sysop" => [
				"Admin" => 1,
				"Admin AWB Account" => 1,
			],
			"formeradmin" => [
				"Admin" => 1,
				"Admin AWB Account" => 1,
			],
			"10k" => [
				"Admin AWB Account" => 1,
				"Admin" => 1,
			],
			"extendedconfirmed" => [
				"Admin AWB Account" => 1,
			],
		];
		$this->assertSame($expected, $actual);
	}

	public function test_sortUsers_sort() {
		// data for this test
		$this->ul->data = [
			'sysop' => [
				'TestUser' => 1,
				'AdminUser' => 1,
			],
			'extendedconfirmed' => [
				'ZzzUser' => 1,
				'RegularUser' => 1,
			],
		];

		// test
		$this->ul->sortUsers();
		$actual = $this->ul->data;
		$expected = [
			'sysop' => [
				'AdminUser' => 1,
				'TestUser' => 1,
			],
			'extendedconfirmed' => [
				'RegularUser' => 1,
				'ZzzUser' => 1,
			],
		];
		$this->assertSame($expected, $actual);
	}
}
