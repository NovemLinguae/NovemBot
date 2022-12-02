<?php

class Controller {
	/**
	 * @param PDO $enwiki
	 * @param PDO $metawiki
	 * @param PDO $centralauth
	 * @param UserList $userlist
	 * @param wikipedia $wp
	 * @param string $wikiUsername
	 * @param string $wikiPassword
	 */
	function __construct($enwiki, $metawiki, $centralauth, $userList, $wp, $wikiUsername, $wikiPassword) {
		$this->enwiki = $enwiki;
		$this->metawiki = $metawiki;
		$this->centralauth = $centralauth;
		$this->userList = $userList;
		$this->wp = $wp;
		$this->wikiUsername = $wikiUsername;
		$this->wikiPassword = $wikiPassword;
	}

	function addCentralAuthUsers($permission) {
		View::echoAndFlush("Get $permission\n");
		$data = Query::getGlobalUsersWithPerm($permission, $this->centralauth);
		$this->userList->addUsers($data, $permission);
		View::echoAndFlush("Done!\n");
	}

	function addMetaUsers($permission) {
		View::echoAndFlush("Get $permission\n");
		$data = Query::getUsersWithPerm($permission, $this->metawiki);
		$this->userList->addUsers($data, $permission);
		View::echoAndFlush("Done!\n");
	}

	function addEnwikiUsers($permission) {
		View::echoAndFlush("Get $permission\n");
		$data = Query::getUsersWithPerm($permission, $this->enwiki);
		$this->userList->addUsers($data, $permission);
		View::echoAndFlush("Done!\n");
	}

	function addFormerAdmins() {
		View::echoAndFlush("Get formeradmins\n");
		$data1 = Query::getAllAdminsEverEnwiki($this->enwiki);
		$data2 = Query::getAllAdminsEverMetawiki($this->metawiki);
		$data = array_merge($data1, $data2);
		$this->userList->addUsers($data, 'formeradmin');
		View::echoAndFlush("Done!\n");
	}

	function addEnwikiUsersByEditCount($permission, $minimumEditCount) {
		View::echoAndFlush("Get $permission\n");
		$data = Query::getUsersWithEditCount($minimumEditCount, $this->enwiki);
		$this->userList->addUsers($data, $permission);
		View::echoAndFlush("Done!\n");
	}

	function logIn() {
		View::echoAndFlush("\nLogging in...\n");
		$this->wp->http->useragent = '[[en:User:NovemBot]] task A, owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
		$this->wp->login($this->wikiUsername, $this->wikiPassword);
		View::echoAndFlush("Done!\n");
	}

	function importJsonFromEnwiki($permission, $enwikiNamespaceAndPage) {

	}

	function writeUpdate() {

	}
}
