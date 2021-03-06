<?php

// Whole program takes 17-41 seconds. Awesome.
// flushing doesn't appear to work on Toolforge web due to gzip compression. Works in CLI though.
// TODO: change NovemBot password, and use a token

Class DataRetriever {
	// Takes 0 seconds
	static function get_users_with_perm($perm, $db) {
		$query = $db->prepare("
			SELECT user_name
			FROM user
			JOIN user_groups ON ug_user = user_id
			WHERE ug_group = '".$perm."'
			ORDER BY user_name ASC;
		");
		$query->execute();
		return $query->fetchAll();
	}

	static function get_global_users_with_perm($perm, $db) {
		$query = $db->prepare("
			SELECT gu_name
			FROM globaluser
			JOIN global_user_groups ON gug_user = gu_id
			WHERE gug_group = '".$perm."'
			ORDER BY gu_name ASC;
		");
		$query->execute();
		return $query->fetchAll();
	}
	
	// For 10k, takes 11 to 22 seconds. Removing ORDER BY doesn't speed it up.
	static function get_users_with_edit_count($minimum_edits, $db) {
		$query = $db->prepare("
			SELECT user_name
			FROM user
			WHERE user_editcount >= ".$minimum_edits."
			ORDER BY user_editcount DESC;
		");
		$query->execute();
		return $query->fetchAll();
	}
}

Class UserList {
	function __constructor() {
		$this->data = [];
	}

	function _addHardCodedSocks() {
		// Name changes, legitimate alternative accounts ===============
		$this->data['steward']['DeltaQuad'] = 1; // AmandaNP

		$this->data['sysadmin']['Jdlrobson'] = 1; // Jon (WMF)

		$this->data['arbcom']['AdmiralEek'] = 1; // CaptainEek
		$this->data['arbcom']['IznoPublic'] = 1;
		$this->data['arbcom']['IznoRepeat'] = 1;
		$this->data['arbcom']['Wyrm That Turned'] = 1; // Worm That Turned

		$this->data['bureaucrat']['Xeno (WMF)'] = 1;
	
		$this->data['sysop']['PEIsquirrel'] = 1; // Ivanvector
		$this->data['sysop']['SubjectiveNotability'] = 1; // GeneralNotability
		$this->data['sysop']['In actu'] = 1; // Guerilero
		$this->data['sysop']['Nyttend backup'] = 1;
		$this->data['sysop']['TNTPublic'] = 1; // TheresNoTime
		$this->data['sysop']['Iridescent 2'] = 1;
		$this->data['sysop']['Awkward42'] = 1; //Thryduulf
		$this->data['sysop']['ToBeFree (mobile)'] = 1;
		$this->data['sysop']['C678'] = 1; // Cyberpower678
		$this->data['sysop']['RoySmith-Mobile'] = 1; // RoySmith
		$this->data['sysop']['Amory'] = 1; // Amorymeltzer
		$this->data['sysop']['Bishzilla'] = 1; // Bishonen
		$this->data['sysop']['Darwinbish'] = 1; // Bishonen
		$this->data['sysop']['Bishapod'] = 1; // Bishonen
		$this->data['sysop']['Darwinfish'] = 1; // Bishonen
		$this->data['sysop']['TheSandDoctor (mobile)'] = 1;

		$this->data['patroller']['Mikehawk10'] = 1; // Mhawk10
		$this->data['patroller']['Guy Macon Alternate Account'] = 1;
		$this->data['patroller']['Power~enwiki'] = 1; // ???
		$this->data['patroller']['Bri.public'] = 1;
		$this->data['patroller']['Joel B. Lewis'] = 1; //JayBeeEll
		$this->data['patroller']['Fortuna Imperatrix Mundi'] = 1; // Serial Number 54129
		$this->data['patroller']['McClenon mobile'] = 1; // Robert McClenon

		$this->data['10k']['Dr. Blofeld'] = 1; // Encyclop??dius
		$this->data['10k']['Jd02022092'] = 1; // JalenFolf
	}

	function flatten_sql($list) {
		$flattened = [];
		foreach ( $list as $value ) {
			$flattened[$value[0]] = 1;
		}
		return $flattened;
	}
	
	/** Input should be in the format ['username1', 'username2', 'etc.'] */
	function addList($list, $name) {
		$this->data[$name] = $this->flatten_sql($list);
	}
	
	/** Input should already be in the ['username'] = 1 format. */
	function addProperlyFormatted($json, $name) {
		$this->data[$name] = $json;
	}
	
	// Array with multiple perms
	function get_all_json() {
		$this->_addHardCodedSocks();
		// Format data. Escape backslashes.
		return json_encode($this->data, JSON_UNESCAPED_UNICODE);
	}
	
	// Array with one perm only, and it is flatter than the multiple perm array
	function get_one_json() {
		$this->_addHardCodedSocks();
		$first_key = array_key_first($this->data);
		return json_encode($this->data[$first_key], JSON_UNESCAPED_UNICODE);
	}
}

function echoAndFlush($str) {
	echo $str;
	//ob_flush();
	//flush();
}

//@ini_set('zlib.output_compression',0);
//@ini_set('implicit_flush',1);
//@ob_end_clean();
//set_time_limit(0);
//if (ob_get_level() == 0) ob_start();
//ob_implicit_flush(true);
//ob_end_flush();

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

/*
// workaround for Windows/localhost, where posix_* is not defined, and cannot be installed on Windows
if ( ! function_exists('posix_getpwuid') ) {
	function posix_getpwuid($user_id) {
		return ['dir' => '..'];
	}

	function posix_getuid() {
		return '';
	}
}
*/

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
$data = DataRetriever::get_global_users_with_perm('founder', $centralauth);
$ul->addList($data, 'founder');
echoAndFlush("Done!\n");

echoAndFlush("Get steward\n");
$data = DataRetriever::get_global_users_with_perm('steward', $centralauth);
$ul->addList($data, 'steward');
echoAndFlush("Done!\n");

echoAndFlush("Get sysadmin\n");
$data = DataRetriever::get_global_users_with_perm('sysadmin', $centralauth);
$ul->addList($data, 'sysadmin');
echoAndFlush("Done!\n");

echoAndFlush("Get staff\n");
$data = DataRetriever::get_global_users_with_perm('staff', $centralauth);
$ul->addList($data, 'staff');
echoAndFlush("Done!\n");

echoAndFlush("Get global-interface-editor\n");
$data = DataRetriever::get_global_users_with_perm('global-interface-editor', $centralauth);
$ul->addList($data, 'global-interface-editor');
echoAndFlush("Done!\n");

// META ==========================================

echoAndFlush("Get wmf-supportsafety\n");
$data = DataRetriever::get_users_with_perm('wmf-supportsafety', $metawiki);
$ul->addList($data, 'wmf-supportsafety');
echoAndFlush("Done!\n");

// EN-WIKI =======================================

echoAndFlush("Get bureaucrat\n");
$data = DataRetriever::get_users_with_perm('bureaucrat', $enwiki);
$ul->addList($data, 'bureaucrat');
echoAndFlush("Done!\n");

echoAndFlush("Get sysop\n");
$data = DataRetriever::get_users_with_perm('sysop', $enwiki);
$ul->addList($data, 'sysop');
echoAndFlush("Done!\n");

echoAndFlush("Get patroller\n");
$data = DataRetriever::get_users_with_perm('patroller', $enwiki);
$ul->addList($data, 'patroller');
echoAndFlush("Done!\n");

echoAndFlush("Get extendedconfirmed\n");
$data = DataRetriever::get_users_with_perm('extendedconfirmed', $enwiki);
$ul->addList($data, 'extendedconfirmed');
echoAndFlush("Done!\n");

echoAndFlush("Get bot\n");
$data = DataRetriever::get_users_with_perm('bot', $enwiki);
$ul->addList($data, 'bot');
echoAndFlush("Done!\n");

echoAndFlush("Get 10k editors\n");
$data = DataRetriever::get_users_with_edit_count(10000, $enwiki);
$ul->addList($data, '10k');
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

//print_r($ul->get_all_json());

echoAndFlush("\nWriting data to User:NovemBot subpage...\n");
$page_contents = $ul->get_all_json();
$objwiki->edit(
	'User:NovemBot/userlist.js',
	$page_contents,
	'Update list of users who have permissions (NovemBot Task A)'
);
echoAndFlush("...done.\n");

/*
echoAndFlush("\nWrite it again to get past the edit filter...\n";
$objwiki->edit(
	'User:NovemBot/userlist.js',
	$page_contents,
	'Bot edit to keep user list up to date.'
);
echoAndFlush("...done.\n";
*/

echoAndFlush("\nMission accomplished.\n\n"); // extra line breaks at end for CLI
//ob_end_flush();

// We need to use the replica databases to get the data we need.
// The replicas are kept in sync with the master database, so if there's a delay, it's only minutes. Not a big deal.
// The API limits userlists to 500 rows, so the API is no good.
// https://wikitech.wikimedia.org/wiki/Help:Toolforge/Database
// https://wikitech.wikimedia.org/wiki/Help:Toolforge/Database#PHP_(using_PDO)
// Replica database access requires ToolForge.

/*
	patroller = npp
	reviewer = PC reviewer
	autoreviewer = autopatrolled
	rollbacker
	filemover
	extendedmover = page mover
	templateeditor
	abusefilter = edit filter manager
	abusefilter-helper = edit filter helper
*/

// TODO: list of and color for former admins