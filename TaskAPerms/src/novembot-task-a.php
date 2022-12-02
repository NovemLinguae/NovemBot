<?php

// https://novem-bot.toolforge.org/task-a/novembot-task-a.php?password=

// TODO: the "GET X" code below has a bunch of repetition, extract those into functions

require_once('botclasses.php');
require_once('Controller.php');
require_once('Database.php');
require_once('HardCodedSocks.php');
require_once('logininfo.php');
require_once('Query.php');
require_once('UserList.php');
require_once('View.php');

View::setHeaders();
View::setErrorReporting();
// set_time_limit(1440);    # 24 minutes

View::dieIfInvalidPassword($httpGetPassword);

View::echoAndFlush("PHP version: " . PHP_VERSION . "\n\n");

$enwiki = Database::create('enwiki');
$metawiki = Database::create('metawiki');
$centralauth = Database::create('centralauth');
$ul = new UserList();
$wp = new wikipedia();

$c = new Controller($enwiki, $metawiki, $centralauth, $ul, $wp, $wikiUsername, $wikiPassword);

$c->addCentralAuthUsers('founder');
$c->addCentralAuthUsers('steward');
$c->addCentralAuthUsers('sysadmin');
$c->addCentralAuthUsers('staff');
$c->addCentralAuthUsers('global-interface-editor');
$c->addCentralAuthUsers('global-sysop');

$c->addMetaUsers('wmf-supportsafety');

$c->addEnwikiUsers('bureaucrat');
$c->addEnwikiUsers('sysop');
$c->addEnwikiUsers('patroller'); // New Page Patroller
$c->addEnwikiUsers('bot');
$c->addEnwikiUsers('checkuser');
$c->addEnwikiUsers('suppress'); // Oversighter

$c->addFormerAdmins();

$c->addEnwikiUsersByEditCount('extendedconfirmed', 500);
$c->addEnwikiUsersByEditCount('10k', 10000);

$c->logIn();

View::echoAndFlush("\nGet arbcom\n");
$data = $wp->getpage('User:AmoryBot/crathighlighter.js/arbcom.json');
$data = json_decode($data, true);
$ul->addProperlyFormatted($data, 'arbcom');
View::echoAndFlush("...done.\n");

View::echoAndFlush("\nGet productive IPs\n");
$data = $wp->getpage('User:Novem_Linguae/User_lists/Productive_IPs.js');
$data = json_decode($data, true);
$ul->addProperlyFormatted($data, 'productiveIPs');
View::echoAndFlush("...done.\n");

View::echoAndFlush("\nWriting data to User:NovemBot subpage...\n");
$page_contents = $ul->getAllJson();
$wp->edit(
	'User:NovemBot/userlist.js',
	$page_contents,
	'Update list of users who have permissions (NovemBot Task A)'
);
View::echoAndFlush("...done.\n");

View::echoAndFlush("\nMission accomplished.\n\n"); // extra line breaks at end for CLI
