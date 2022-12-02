<?php

// https://novem-bot.toolforge.org/task-a/novembot-task-a.php?password=

// Things to refactor now that I'm looking at this a year later...
// TODO: the "GET X" code below has a bunch of repetition, extract those into functions
// TODO: get hard coded socks out of this and into its own JSON file. if i want to keep the comments, make those data instead. so something like { 'name': 'abc', 'comment': 'def' }. or use .yml, which supports #comments. example .yml file in MusikBot repository
// TODO: delete dead code and thick comment blocks
// TODO: better class names, better file names

require_once('Query.php');
require_once('HardCodedSocks.php');

Class UserList {
	function __constructor() {
		$this->data = [];
	}

	function _addHardCodedSocks() {
		$this->data = HardCodedSocks::add($this->data);
	}

	function _addLinkedUsernames() {
		$this->_buildLinkedUsernamesList();
		$this->_linkMainAndAltUsernames();
	}

	function _buildLinkedUsernamesList() {
		// { "oldName": "currentName" }
		$fileString = file_get_contents('LinkedUsernames.json', true);
		// $this->linkedUsernames['oldName'] = 'currentName';
		$this->linkedUsernames = json_decode($fileString, true);
	}

	function _linkMainAndAltUsernames() {
		foreach ( $this->linkedUsernames as $altUsername => $mainUsername ) {
			foreach ( $this->data as $permission => $arrayOfUsernames ) {
				if ( $this->data[$permission][$mainUsername] ?? '' ) {
					$this->data[$permission][$altUsername] = 1;
				}
			}
		}
	}

	function flatten_sql($list) {
		$flattened = [];
		foreach ( $list as $value ) {
			$flattened[$value[0]] = 1;
		}
		return $flattened;
	}
	
	/**
	 * Input should be in the format ['username1', 'username2', 'etc.']
	 */
	function addUsers($list, $permission) {
		$this->data[$permission] = $this->flatten_sql($list);
	}
	
	/**
	 * Input should already be in the ['username'] = 1 format.
	 */
	function addProperlyFormatted($json, $permission) {
		$this->data[$permission] = $json;
	}
	
	/**
	 * @return array Array with multiple perms
	 */
	function get_all_json() {
		$this->_addHardCodedSocks();
		$this->_addLinkedUsernames();
		// Format data. Escape backslashes.
		return json_encode($this->data, JSON_UNESCAPED_UNICODE);
	}
	
	/**
	 * @return array Array with one perm only, and it is flatter than the multiple perm array
	 */
	function get_one_json() {
		$this->_addHardCodedSocks();
		$this->_addLinkedUsernames();
		$first_key = array_key_first($this->data);
		return json_encode($this->data[$first_key], JSON_UNESCAPED_UNICODE);
	}
}

/**
 * flushing (ob_flush, flush) doesn't appear to work on Toolforge web due to gzip compression. Works in CLI though.
 */
function echoAndFlush($str) {
	echo $str;
}

header('Content-Type:text/plain; charset=utf-8; Content-Encoding: none'); // Content-Encoding: none disables gzip compression, which may help fix an issue with flushing
// set_time_limit(1440);    # 24 minutes
ini_set("display_errors", 1);
error_reporting(E_ALL);
include("botclasses.php");
include("logininfo.php");

// Keep randos from running the bot in browser and in bash
if ( ($_GET['password'] ?? '') != $http_get_password && ($argv[1] ?? '') != $http_get_password ) {
	die('Invalid password.');
}

echoAndFlush("PHP version: " . PHP_VERSION . "\n\n");

// connect to replica SQL databases
$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");

// Must use database_p. database will not work.
$enwiki = new PDO("mysql:host=enwiki.analytics.db.svc.wikimedia.cloud;dbname=enwiki_p", $ts_mycnf['user'], $ts_mycnf['password']);
$metawiki = new PDO("mysql:host=metawiki.analytics.db.svc.wikimedia.cloud;dbname=metawiki_p", $ts_mycnf['user'], $ts_mycnf['password']);
$centralauth = new PDO("mysql:host=metawiki.analytics.db.svc.wikimedia.cloud;dbname=centralauth_p", $ts_mycnf['user'], $ts_mycnf['password']);

$enwiki->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
$metawiki->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
$centralauth->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

$ul = new UserList();

// CENTRALAUTH =========================================

echoAndFlush("Get founder\n");
$data = Query::getGlobalUsersWithPerm('founder', $centralauth);
$ul->addUsers($data, 'founder');
echoAndFlush("Done!\n");

echoAndFlush("Get steward\n");
$data = Query::getGlobalUsersWithPerm('steward', $centralauth);
$ul->addUsers($data, 'steward');
echoAndFlush("Done!\n");

echoAndFlush("Get sysadmin\n");
$data = Query::getGlobalUsersWithPerm('sysadmin', $centralauth);
$ul->addUsers($data, 'sysadmin');
echoAndFlush("Done!\n");

echoAndFlush("Get staff\n");
$data = Query::getGlobalUsersWithPerm('staff', $centralauth);
$ul->addUsers($data, 'staff');
echoAndFlush("Done!\n");

echoAndFlush("Get global-interface-editor\n");
$data = Query::getGlobalUsersWithPerm('global-interface-editor', $centralauth);
$ul->addUsers($data, 'global-interface-editor');
echoAndFlush("Done!\n");

echoAndFlush("Get global-sysop\n");
$data = Query::getGlobalUsersWithPerm('global-sysop', $centralauth);
$ul->addUsers($data, 'global-sysop');
echoAndFlush("Done!\n");

// META ==========================================

echoAndFlush("Get wmf-supportsafety\n");
$data = Query::getUsersWithPerm('wmf-supportsafety', $metawiki);
$ul->addUsers($data, 'wmf-supportsafety');
echoAndFlush("Done!\n");

// EN-WIKI =======================================

echoAndFlush("Get bureaucrat\n");
$data = Query::getUsersWithPerm('bureaucrat', $enwiki);
$ul->addUsers($data, 'bureaucrat');
echoAndFlush("Done!\n");

echoAndFlush("Get sysop\n");
$data = Query::getUsersWithPerm('sysop', $enwiki);
$ul->addUsers($data, 'sysop');
echoAndFlush("Done!\n");

echoAndFlush("Get formeradmins\n");
$data1 = Query::getAllAdminsEverEnwiki($enwiki);
$data2 = Query::getAllAdminsEverMetawiki($metawiki);
$data = array_merge($data1, $data2);
$ul->addUsers($data, 'formeradmin');
echoAndFlush("Done!\n");

echoAndFlush("Get patroller\n");
$data = Query::getUsersWithPerm('patroller', $enwiki);
$ul->addUsers($data, 'patroller');
echoAndFlush("Done!\n");

echoAndFlush("Get extendedconfirmed\n");
$data = Query::getUsersWithEditCount(500, $enwiki); // doing by edit count instead of perm gets 14,000 additional users, and captures users who have above 500 edits but for some reason don't have the extendedconfirmed perm. however this is the slowest query we do, taking around 3 minutes
$ul->addUsers($data, 'extendedconfirmed');
echoAndFlush("Done!\n");

echoAndFlush("Get bot\n");
$data = Query::getUsersWithPerm('bot', $enwiki);
$ul->addUsers($data, 'bot');
echoAndFlush("Done!\n");

echoAndFlush("Get checkuser\n");
$data = Query::getUsersWithPerm('checkuser', $enwiki);
$ul->addUsers($data, 'checkuser');
echoAndFlush("Done!\n");

echoAndFlush("Get suppress\n"); // oversighter
$data = Query::getUsersWithPerm('suppress', $enwiki);
$ul->addUsers($data, 'suppress');
echoAndFlush("Done!\n");

echoAndFlush("Get 10k editors\n");
$data = Query::getUsersWithEditCount(10000, $enwiki);
$ul->addUsers($data, '10k');
echoAndFlush("Done!\n");



// We'll use the API to write our data to User:NovemBot/xyz
echoAndFlush("\nLogging in...\n");
$objwiki = new wikipedia();
$objwiki->http->useragent = '[[en:User:NovemBot]] task A, owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
$objwiki->login($wiki_username, $wiki_password);
echoAndFlush("Done!\n");




echoAndFlush("\nGet arbcom\n");
$data = $objwiki->getpage('User:AmoryBot/crathighlighter.js/arbcom.json');
$data = json_decode($data, true);
$ul->addProperlyFormatted($data, 'arbcom');
echoAndFlush("...done.\n");

echoAndFlush("\nGet productive IPs\n");
$data = $objwiki->getpage('User:Novem_Linguae/User_lists/Productive_IPs.js');
$data = json_decode($data, true);
$ul->addProperlyFormatted($data, 'productiveIPs');
echoAndFlush("...done.\n");





echoAndFlush("\nWriting data to User:NovemBot subpage...\n");
$page_contents = $ul->get_all_json();
$objwiki->edit(
	'User:NovemBot/userlist.js',
	$page_contents,
	'Update list of users who have permissions (NovemBot Task A)'
);
echoAndFlush("...done.\n");

echoAndFlush("\nMission accomplished.\n\n"); // extra line breaks at end for CLI
