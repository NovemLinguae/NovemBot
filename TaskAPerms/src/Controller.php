<?php

class Controller {
	protected $enwiki;
	protected $metawiki;
	protected $centralauth;
	protected $userList;
	protected $wp;
	protected $wikiUsername;
	protected $wikiPassword;

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
		View::print("Get $permission\n");
		$data = Query::getGlobalUsersWithPerm($permission, $this->centralauth);
		$this->userList->addUsers($data, $permission);
		View::print("Done!\n");
	}

	function addMetaUsers($permission) {
		View::print("Get $permission\n");
		$data = Query::getUsersWithPerm($permission, $this->metawiki);
		$this->userList->addUsers($data, $permission);
		View::print("Done!\n");
	}

	function addEnwikiUsers($permission) {
		View::print("Get $permission\n");
		$data = Query::getUsersWithPerm($permission, $this->enwiki);
		$this->userList->addUsers($data, $permission);
		View::print("Done!\n");
	}

	function addFormerAdmins() {
		View::print("Get formeradmins\n");
		$data1 = Query::getAllAdminsEverEnwiki($this->enwiki);
		$data2 = Query::getAllAdminsEverMetawiki($this->metawiki);
		$data = array_merge($data1, $data2);
		$this->userList->addUsers($data, 'formeradmin');
		View::print("Done!\n");
	}

	function addEnwikiUsersByEditCount($permission, $minimumEditCount) {
		View::print("Get $permission\n");
		$data = Query::getUsersWithEditCount($minimumEditCount, $this->enwiki);
		$this->userList->addUsers($data, $permission);
		View::print("Done!\n");
	}

	function logIn() {
		View::print("\nLogging in...\n");
		$this->wp->http->useragent = '[[en:User:NovemBot]] task A, owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
		$this->wp->login($this->wikiUsername, $this->wikiPassword);
		View::print("Done!\n");
	}

	/**
	 * @param string $permission
	 * @param string $enwikiNamespaceAndPage
	 */
	function addUsersFromEnwikiJsonPage($permission, $enwikiNamespaceAndPage) {
		View::print("\nGet $permission\n");
		$data = $this->wp->getpage($enwikiNamespaceAndPage);
		$data = json_decode($data, true);
		$this->userList->addProperlyFormatted($data, $permission);
		View::print("...done.\n");
	}

	function writeUpdate() {
		View::print("\nWriting data to User:NovemBot subpage...\n");
		$page_contents = $this->userList->getAllJson();
		$this->wp->edit(
			'User:NovemBot/userlist.js',
			$page_contents,
			'Update list of users who have permissions (NovemBot Task A)'
		);
		View::print("...done.\n");
	}
}
