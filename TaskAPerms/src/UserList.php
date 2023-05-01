<?php

class UserList {
	/** Making these public for unit test reasons */
	public $data;
	public $linkedUsernames;

	public function __constructor() {
		$this->data = [];
	}

	public function flatten_sql($list) {
		$flattened = [];
		foreach ( $list as $value ) {
			$flattened[$value[0]] = 1;
		}
		return $flattened;
	}

	/**
	 * @param array $list should be in the format ['username1', 'username2', 'etc.']
	 * @param string $permission
	 */
	public function addUsers($list, $permission) {
		$this->data[$permission] = $this->flatten_sql($list);
	}

	/**
	 * @param array $json should be in the ['username'] = 1 format.
	 * @param string $permission
	 */
	public function addProperlyFormatted($json, $permission) {
		$this->data[$permission] = $json;
	}

	/**
	 * @return array Array with multiple perms
	 */
	public function getAllJson() {
		$this->addHardCodedSocks();
		$this->addLinkedUsernames();
		// Format data. Escape backslashes.
		return json_encode($this->data, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Used for debugging.
	 *
	 * @return array Array with one perm only, and it is flatter than the multiple perm array
	 */
	public function getOneJson() {
		$this->addHardCodedSocks();
		$this->addLinkedUsernames();
		$first_key = array_key_first($this->data);
		return json_encode($this->data[$first_key], JSON_UNESCAPED_UNICODE);
	}

	private function addHardCodedSocks() {
		$this->data = HardCodedSocks::add($this->data);
	}

	private function addLinkedUsernames() {
		$this->buildLinkedUsernamesList();
		$this->linkMainAndAltUsernames();
	}

	private function buildLinkedUsernamesList() {
		// { "oldName": "currentName" }
		$fileString = file_get_contents('LinkedUsernames.json', true);
		// $this->linkedUsernames['oldName'] = 'currentName';
		$this->linkedUsernames = json_decode($fileString, true);
	}

	/**
	 * Making this public so I can unit test it
	 */
	public function linkMainAndAltUsernames() {
		foreach ( $this->linkedUsernames as $altUsername => $mainUsername ) {
			foreach ( $this->data as $permission => $arrayOfUsernames ) {
				if ( $this->data[$permission][$mainUsername] ?? '' ) {
					$this->data[$permission][$altUsername] = 1;
				}
			}
		}
	}
}
