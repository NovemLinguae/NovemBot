<?php

// https://novem-bot.toolforge.org/task-a/novembot-task-a.php?password=

// TODO: the "GET X" code below has a bunch of repetition, extract those into functions

require_once("botclasses.php");
require_once('Database.php');
require_once('HardCodedSocks.php');
require_once("logininfo.php");
require_once('Query.php');
require_once('UserList.php');
require_once('View.php');

View::setHeaders();
View::setErrorReporting();
// set_time_limit(1440);    # 24 minutes

View::dieIfInvalidPassword($http_get_password);

View::echoAndFlush("PHP version: " . PHP_VERSION . "\n\n");

$enwiki = Database::create('enwiki');
$metawiki = Database::create('metawiki');
$centralauth = Database::create('centralauth');

$ul = new UserList();

// CENTRALAUTH =========================================

View::echoAndFlush("Get founder\n");
$data = Query::getGlobalUsersWithPerm('founder', $centralauth);
$ul->addUsers($data, 'founder');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get steward\n");
$data = Query::getGlobalUsersWithPerm('steward', $centralauth);
$ul->addUsers($data, 'steward');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get sysadmin\n");
$data = Query::getGlobalUsersWithPerm('sysadmin', $centralauth);
$ul->addUsers($data, 'sysadmin');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get staff\n");
$data = Query::getGlobalUsersWithPerm('staff', $centralauth);
$ul->addUsers($data, 'staff');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get global-interface-editor\n");
$data = Query::getGlobalUsersWithPerm('global-interface-editor', $centralauth);
$ul->addUsers($data, 'global-interface-editor');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get global-sysop\n");
$data = Query::getGlobalUsersWithPerm('global-sysop', $centralauth);
$ul->addUsers($data, 'global-sysop');
View::echoAndFlush("Done!\n");

// META ==========================================

View::echoAndFlush("Get wmf-supportsafety\n");
$data = Query::getUsersWithPerm('wmf-supportsafety', $metawiki);
$ul->addUsers($data, 'wmf-supportsafety');
View::echoAndFlush("Done!\n");

// EN-WIKI =======================================

View::echoAndFlush("Get bureaucrat\n");
$data = Query::getUsersWithPerm('bureaucrat', $enwiki);
$ul->addUsers($data, 'bureaucrat');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get sysop\n");
$data = Query::getUsersWithPerm('sysop', $enwiki);
$ul->addUsers($data, 'sysop');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get formeradmins\n");
$data1 = Query::getAllAdminsEverEnwiki($enwiki);
$data2 = Query::getAllAdminsEverMetawiki($metawiki);
$data = array_merge($data1, $data2);
$ul->addUsers($data, 'formeradmin');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get patroller\n");
$data = Query::getUsersWithPerm('patroller', $enwiki);
$ul->addUsers($data, 'patroller');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get extendedconfirmed\n");
$data = Query::getUsersWithEditCount(500, $enwiki); // doing by edit count instead of perm gets 14,000 additional users, and captures users who have above 500 edits but for some reason don't have the extendedconfirmed perm. however this is the slowest query we do, taking around 3 minutes
$ul->addUsers($data, 'extendedconfirmed');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get bot\n");
$data = Query::getUsersWithPerm('bot', $enwiki);
$ul->addUsers($data, 'bot');
View::echoAndFlush("Done!\n");

// Not used by UserHighlighterSimple, but could potentially be used by other scripts
View::echoAndFlush("Get checkuser\n");
$data = Query::getUsersWithPerm('checkuser', $enwiki);
$ul->addUsers($data, 'checkuser');
View::echoAndFlush("Done!\n");

// Not used by UserHighlighterSimple, but could potentially be used by other scripts
View::echoAndFlush("Get suppress\n"); // oversighter
$data = Query::getUsersWithPerm('suppress', $enwiki);
$ul->addUsers($data, 'suppress');
View::echoAndFlush("Done!\n");

View::echoAndFlush("Get 10k editors\n");
$data = Query::getUsersWithEditCount(10000, $enwiki);
$ul->addUsers($data, '10k');
View::echoAndFlush("Done!\n");



// We'll use the API to write our data to User:NovemBot/xyz
View::echoAndFlush("\nLogging in...\n");
$objwiki = new wikipedia();
$objwiki->http->useragent = '[[en:User:NovemBot]] task A, owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
$objwiki->login($wiki_username, $wiki_password);
View::echoAndFlush("Done!\n");




View::echoAndFlush("\nGet arbcom\n");
$data = $objwiki->getpage('User:AmoryBot/crathighlighter.js/arbcom.json');
$data = json_decode($data, true);
$ul->addProperlyFormatted($data, 'arbcom');
View::echoAndFlush("...done.\n");

View::echoAndFlush("\nGet productive IPs\n");
$data = $objwiki->getpage('User:Novem_Linguae/User_lists/Productive_IPs.js');
$data = json_decode($data, true);
$ul->addProperlyFormatted($data, 'productiveIPs');
View::echoAndFlush("...done.\n");





View::echoAndFlush("\nWriting data to User:NovemBot subpage...\n");
$page_contents = $ul->getAllJson();
$objwiki->edit(
	'User:NovemBot/userlist.js',
	$page_contents,
	'Update list of users who have permissions (NovemBot Task A)'
);
View::echoAndFlush("...done.\n");

View::echoAndFlush("\nMission accomplished.\n\n"); // extra line breaks at end for CLI
