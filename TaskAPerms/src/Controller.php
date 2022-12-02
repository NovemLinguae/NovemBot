<?php

class Controller {
	function __construct($enwiki, $metawiki, $centralauth, $userList) {
		$this->enwiki = $enwiki;
		$this->metawiki = $metawiki;
		$this->centralauth = $centralauth;
		$this->userList = $userList;
	}

	function addCentralAuthUsers($permission) {
		View::echoAndFlush("Get $permission\n");
		$data = Query::getGlobalUsersWithPerm($permission, $this->centralauth);
		$this->userList->addUsers($data, $permission);
		View::echoAndFlush("Done!\n");
	}
}
