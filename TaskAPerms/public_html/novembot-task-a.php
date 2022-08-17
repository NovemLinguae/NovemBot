<?php

// flushing doesn't appear to work on Toolforge web due to gzip compression. Works in CLI though.
// https://novem-bot.toolforge.org/task-a/novembot-task-a.php?password=

// Things to refactor now that I'm looking at this a year later...
// TODO: the "GET X" code below has a bunch of repetition, extract those into functions
// TODO: get hard coded socks out of this and into its own JSON file. if i want to keep the comments, make those data instead. so something like { 'name': 'abc', 'comment': 'def' }. or use .yml, which supports #comments. example .yml file in MusikBot repository
// TODO: create a JSON file that links renames to current accounts
// TODO: delete dead code and thick comment blocks
// TODO: better class names, better file names

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
		// globaluser is a special SQL table created by [[mw:Extension:CentralAuth]]
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

	/** Includes former admins */
	static function get_all_admins_ever_enwiki_db($db) {
		$query = $db->prepare("
			SELECT DISTINCT REPLACE(log_title, '_', ' ') AS promoted_to_admin
			FROM logging
			WHERE log_type = 'rights'
				AND log_action = 'rights'
				AND log_params LIKE '%sysop%'
			ORDER BY log_title ASC;
		");
		$query->execute();
		return $query->fetchAll();
	}

	/** Includes former admins */
	static function get_all_admins_ever_metawiki_db($db) {
		$query = $db->prepare("
			SELECT DISTINCT REPLACE(REPLACE(log_title, '_', ' '), '@enwiki', '') AS promoted_to_admin
			FROM logging_logindex
			WHERE log_type = 'rights'
				AND log_action = 'rights'
				AND log_title LIKE '%@enwiki'
				AND log_params LIKE '%sysop%'
			ORDER BY log_title ASC
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
		// https://gerrit.wikimedia.org/r/admin/groups/4cdcb3a1ef2e19d73bc9a97f1d0f109d2e0209cd,members
		$this->data['mediawikiPlusTwo']['Aaron Schulz'] = 1;
		$this->data['mediawikiPlusTwo']['Addshore'] = 1;
		$this->data['mediawikiPlusTwo']['Ammarpad'] = 1;
		$this->data['mediawikiPlusTwo']['Anomie'] = 1;
		$this->data['mediawikiPlusTwo']['Aude'] = 1;
		$this->data['mediawikiPlusTwo']['Awjrichards'] = 1;
		$this->data['mediawikiPlusTwo']['Matma Rex'] = 1; // Bartosz Dziewoński
		$this->data['mediawikiPlusTwo']['Brion VIBBER'] = 1;
		$this->data['mediawikiPlusTwo']['Catrope'] = 1; // Roan Kattouw
		$this->data['mediawikiPlusTwo']['Daimona Eaytoy'] = 1;
		$this->data['mediawikiPlusTwo']['DannyS712'] = 1;
		$this->data['mediawikiPlusTwo']['Glaisher'] = 1;
		$this->data['mediawikiPlusTwo']['Hashar'] = 1; // Antoine Musso
		$this->data['mediawikiPlusTwo']['Hoo man'] = 1; // also a steward
		$this->data['mediawikiPlusTwo']['Huji'] = 1;
		$this->data['mediawikiPlusTwo']['Jack Phoenix'] = 1;
		$this->data['mediawikiPlusTwo']['Jackmcbarn'] = 1;
		$this->data['mediawikiPlusTwo']['JanZerebecki'] = 1;
		$this->data['mediawikiPlusTwo']['Kaldari'] = 1;
		$this->data['mediawikiPlusTwo']['Krinkle'] = 1;
		$this->data['mediawikiPlusTwo']['Ladsgroup'] = 1;
		$this->data['mediawikiPlusTwo']['Legoktm'] = 1;
		$this->data['mediawikiPlusTwo']['Lucas Werkmeister'] = 1;
		$this->data['mediawikiPlusTwo']['Lucas Werkmeister (WMDE)'] = 1;
		$this->data['mediawikiPlusTwo']['Taavi'] = 1; // Majavah
		$this->data['mediawikiPlusTwo']['MarkAHershberger'] = 1;
		$this->data['mediawikiPlusTwo']['Matěj Suchánek'] = 1;
		$this->data['mediawikiPlusTwo']['MaxSem'] = 1;
		$this->data['mediawikiPlusTwo']['Mglaser'] = 1;
		$this->data['mediawikiPlusTwo']['Mvolz'] = 1;
		$this->data['mediawikiPlusTwo']['Parent5446'] = 1;
		$this->data['mediawikiPlusTwo']['Platonides'] = 1;
		$this->data['mediawikiPlusTwo']['PleaseStand'] = 1;
		$this->data['mediawikiPlusTwo']['Reedy'] = 1;
		$this->data['mediawikiPlusTwo']['SPQRobin'] = 1;
		$this->data['mediawikiPlusTwo']['Siebrand'] = 1;
		$this->data['mediawikiPlusTwo']['TheDJ'] = 1;
		$this->data['mediawikiPlusTwo']['Thiemo Kreuz (WMDE)'] = 1;
		$this->data['mediawikiPlusTwo']['Tim Starling'] = 1;
		$this->data['mediawikiPlusTwo']['Trevor Parscal'] = 1;
		$this->data['mediawikiPlusTwo']['Umherirrender'] = 1;
		$this->data['mediawikiPlusTwo']['Martin Urbanec'] = 1;
		$this->data['mediawikiPlusTwo']['Christoph Jauera (WMDE)'] = 1; //WMDE-Fisch
		$this->data['mediawikiPlusTwo']['Leszek Manicki (WMDE)'] = 1;

		// On the list of former admins, but not highlighted by the two former admin queries
		// TODO: link these to their renames instead
		$this->data['formeradmin']['168...'] = 1;
		$this->data['formeradmin']['172'] = 1;
		$this->data['formeradmin']['1Angela'] = 1;
		$this->data['formeradmin']['Ævar Arnfjörð Bjarmason'] = 1;
		$this->data['formeradmin']['Andre Engels'] = 1;
		$this->data['formeradmin']['Ark30inf'] = 1;
		$this->data['formeradmin']['Baldhur'] = 1;
		$this->data['formeradmin']['Blankfaze'] = 1;
		$this->data['formeradmin']['Cedar-Guardian'] = 1;
		$this->data['formeradmin']['Chuck Smith'] = 1;
		$this->data['formeradmin']['Fire'] = 1;
		$this->data['formeradmin']['Isis~enwiki'] = 1;
		$this->data['formeradmin']['Jeronim'] = 1;
		$this->data['formeradmin']['Kate'] = 1;
		$this->data['formeradmin']['Kils'] = 1;
		$this->data['formeradmin']['KimvdLinde'] = 1;
		$this->data['formeradmin']['Koyaanis Qatsi'] = 1;
		$this->data['formeradmin']['KRS'] = 1;
		$this->data['formeradmin']['Kyle Barbour'] = 1;
		$this->data['formeradmin']['Looxix'] = 1;
		$this->data['formeradmin']['Mentoz86'] = 1;
		$this->data['formeradmin']['TheCustomOfLife'] = 1;
		$this->data['formeradmin']['Muriel Gottrop'] = 1;
		$this->data['formeradmin']['Paul Benjamin Austin'] = 1;
		$this->data['formeradmin']['Pcb22'] = 1;
		$this->data['formeradmin']['Rootology'] = 1;
		$this->data['formeradmin']['SalopianJames'] = 1;
		$this->data['formeradmin']['Fys'] = 1;
		$this->data['formeradmin']['Secret (renamed)'] = 1;
		$this->data['formeradmin']['Sewing'] = 1;
		$this->data['formeradmin']['Stephen Gilbert'] = 1;
		$this->data['formeradmin']['StringTheory11'] = 1;
		$this->data['formeradmin']['Testuser2'] = 1;
		$this->data['formeradmin']['Vanished user'] = 1;
		$this->data['formeradmin']['Viridian Bovary'] = 1;
		$this->data['formeradmin']['User2004'] = 1;
	}

	function _addLinkedUsernames() {
		$this->_buildLinkedUsernamesList();
		$this->_linkMainAndAltUsernames();
	}

	function _buildLinkedUsernamesList() {
		// $this->linkedUsernames['oldName'] = 'newName';
		$this->linkedUsernames['A. C. Santacruz'] = 'Ixtal';
		$this->linkedUsernames['AdmiralEek'] = 'CaptainEek';
		$this->linkedUsernames['Amory'] = 'Amorymeltzer';
		$this->linkedUsernames['Ashleyyoursmile'] = 'Viridian Bovary';
		$this->linkedUsernames['Awkward42'] = 'Thryduulf';
		$this->linkedUsernames['Bishapod'] = 'Bishonen';
		$this->linkedUsernames['Bishzilla'] = 'Bishonen';
		$this->linkedUsernames['Bri.public'] = 'Bri';
		$this->linkedUsernames['C678'] = 'Cyberpower678';
		$this->linkedUsernames['Chacor'] = 'NSLE';
		$this->linkedUsernames['Cool three'] = 'Cool3';
		$this->linkedUsernames['Darwinbish'] = 'Bishonen';
		$this->linkedUsernames['Darwinfish'] = 'Bishonen';
		$this->linkedUsernames['DeltaQuad'] = 'AmandaNP';
		$this->linkedUsernames['Dr. Blofeld'] = 'Encyclopædius';
		$this->linkedUsernames['Femkemilene'] = 'Femke';
		$this->linkedUsernames['Fortuna Imperatrix Mundi'] = 'Serial Number 54129';
		$this->linkedUsernames['Guy Macon Alternate Account'] = 'Guy Macon';
		$this->linkedUsernames['Gwern'] = 'Marudubshinki';
		$this->linkedUsernames['Hahc21'] = 'Razr Nation';
		$this->linkedUsernames['In actu'] = 'Guerilero';
		$this->linkedUsernames['Iridescent 2'] = 'Iridescent';
		$this->linkedUsernames['IznoPublic'] = 'Izno';
		$this->linkedUsernames['IznoRepeat'] = 'Izno';
		$this->linkedUsernames['JamesBWatson'] = 'JBW';
		$this->linkedUsernames['Jd02022092'] = 'JalenFolf';
		$this->linkedUsernames['Jdlrobson'] = 'Jon (WMF)';
		$this->linkedUsernames['Joel B. Lewis'] = 'JayBeeEll';
		$this->linkedUsernames['Kubigula'] = 'Mojo Hand';
		$this->linkedUsernames['Kwsn'] = 'Alexandria';
		$this->linkedUsernames['Maedin'] = 'Julia W';
		$this->linkedUsernames['McClenon mobile'] = 'Robert McClenon';
		$this->linkedUsernames['Mhawk10'] = 'Red-tailed hawk';
		$this->linkedUsernames['Mikehawk10'] = 'Red-tailed hawk';
		$this->linkedUsernames['Mlpearc'] = 'FlightTime';
		$this->linkedUsernames['Money emoji'] = 'moneytrees';
		$this->linkedUsernames['Nv8200p'] = 'Nv8200pa';
		$this->linkedUsernames['Nyttend backup'] = 'Nyttend';
		$this->linkedUsernames['OberRanks'] = 'Husnock';
		$this->linkedUsernames['PEIsquirrel'] = 'Ivanvector';
		$this->linkedUsernames['Power~enwiki'] = '力';
		$this->linkedUsernames['Prioryman'] = 'ChrisO~enwiki';
		$this->linkedUsernames['QuiteUnusual'] = 'MarcGarver';
		$this->linkedUsernames['Red tailed hawk'] = 'Red-tailed hawk';
		$this->linkedUsernames['RoySmith-Mobile'] = 'RoySmith';
		$this->linkedUsernames['Salvidrim'] = 'Salvidrim!';
		$this->linkedUsernames['Samtar'] = 'TheresNoTime';
		$this->linkedUsernames['SubjectiveNotability'] = 'GeneralNotability';
		$this->linkedUsernames['There\'sNoTime'] = 'TheresNoTime';
		$this->linkedUsernames['TheSandDoctor (mobile)'] = 'TheSandDoctor';
		$this->linkedUsernames['TNTPublic'] = 'TheresNoTime';
		$this->linkedUsernames['ToBeFree (mobile)'] = 'ToBeFree';
		$this->linkedUsernames['Wyrm That Turned'] = 'Worm That Turned';
		$this->linkedUsernames['Ya ya ya ya ya ya'] = 'Freestylefrappe';
		$this->linkedUsernames['Δ'] = 'Betacommand';
		$this->linkedUsernames['CatcherStorm'] = 'LJF2019';
		$this->linkedUsernames['Hhhhhkohhhhh'] = 'Hhkohh';
		$this->linkedUsernames['EricEnfermero'] = 'Larry Hockett';
		$this->linkedUsernames['Babymissfortune'] = 'Engr. Smitty';
		$this->linkedUsernames['Mlpearc Phone'] = 'FlightTime';
		$this->linkedUsernames['Mlpearc'] = 'FlightTime';
		$this->linkedUsernames['Everymorning'] = 'IntoThinAir';
		$this->linkedUsernames['Krishna Chaitanya Velaga'] = 'KCVelaga';
		$this->linkedUsernames['Mduvekot'] = 'Vexations';
		$this->linkedUsernames['Nikolaiho'] = 'Nikolaih';
		$this->linkedUsernames['FireFox'] = '49TL';
		$this->linkedUsernames['HoodedMan'] = 'Madman';
		$this->linkedUsernames['Terenceong1992'] = 'I64s';
		$this->linkedUsernames['Encyclopedist'] = 'Ulises Heureaux';
		$this->linkedUsernames['Aranda56'] = 'Jaranda';
		$this->linkedUsernames['IMatthew'] = 'Gloss';
		$this->linkedUsernames['Coldplay Expert'] = 'White Shadows';
		$this->linkedUsernames['Useight\'s Public Sock'] = 'Useight';
		$this->linkedUsernames['Alison22'] = 'John254';
		$this->linkedUsernames['Candlewicke'] = 'CLWE';
		$this->linkedUsernames['Shawn in Montreal'] = 'Shawn à Montréal';
		$this->linkedUsernames['Bradjamesbrown'] = 'Courcelles';
		$this->linkedUsernames['Warrah'] = 'Ecoleetage';
		$this->linkedUsernames['Microcell'] = 'TheStrayCat';
		$this->linkedUsernames['Tim Song'] = 'Timotheus Canens';
		$this->linkedUsernames['Diego Grez-Cañete'] = 'Vanished user 24kwjf10h32h';
		$this->linkedUsernames['Diego Grez'] = 'Vanished user 24kwjf10h32h';
		$this->linkedUsernames['MisterWiki'] = 'Vanished user 24kwjf10h32h';
		$this->linkedUsernames['Oscroft'] = 'Boing! said Zebedee';
		$this->linkedUsernames['TerrenceandPhillip'] = 'A7x';
		$this->linkedUsernames['TTTSNB'] = 'The Thing That Should Not Be';
		$this->linkedUsernames['Smithers7'] = 'Smithers';
		$this->linkedUsernames['Aleksa Lukic'] = 'VS6507';
		$this->linkedUsernames['TheOriginalSoni'] = 'Soni';
		/*
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		$this->linkedUsernames[''] = '';
		*/
		// $this->linkedUsernames['oldName'] = 'newName';
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
		$this->_addLinkedUsernames();
		// Format data. Escape backslashes.
		return json_encode($this->data, JSON_UNESCAPED_UNICODE);
	}
	
	// Array with one perm only, and it is flatter than the multiple perm array
	function get_one_json() {
		$this->_addHardCodedSocks();
		$this->_addLinkedUsernames();
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

echoAndFlush("Get global-sysop\n");
$data = DataRetriever::get_global_users_with_perm('global-sysop', $centralauth);
$ul->addList($data, 'global-sysop');
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

echoAndFlush("Get formeradmins\n");
$data1 = DataRetriever::get_all_admins_ever_enwiki_db($enwiki);
$data2 = DataRetriever::get_all_admins_ever_metawiki_db($metawiki);
$data = array_merge($data1, $data2);
$ul->addList($data, 'formeradmin');
echoAndFlush("Done!\n");

echoAndFlush("Get patroller\n");
$data = DataRetriever::get_users_with_perm('patroller', $enwiki);
$ul->addList($data, 'patroller');
echoAndFlush("Done!\n");

echoAndFlush("Get extendedconfirmed\n");
$data = DataRetriever::get_users_with_edit_count(500, $enwiki); // doing by edit count instead of perm gets 14,000 additional users, and captures users who have above 500 edits but for some reason don't have the extendedconfirmed perm. however this is the slowest query we do, taking around 3 minutes
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