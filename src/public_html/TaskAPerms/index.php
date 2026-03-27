<?php

// https://novem-bot.toolforge.org/task-a/index.php?password=

ini_set( "display_errors", 1 );
error_reporting( E_ALL );

require_once 'botclasses.php';
require_once 'Controller.php';
require_once 'Database.php';
require_once 'HardCodedSocks.php';
require_once 'logininfo.php';
require_once 'Query.php';
require_once 'UserList.php';
require_once 'View.php';

View::setHeaders();
// set_time_limit(1440);    # 24 minutes

View::dieIfInvalidPassword( $urlAndCliPassword );

View::print( "PHP version: " . PHP_VERSION . "\n\n" );

$enwiki = Database::create( 'enwiki' );
$metawiki = Database::create( 'metawiki' );
$centralauth = Database::create( 'centralauth' );
$ul = new UserList();
$wp = new wikipedia();

$c = new Controller( $enwiki, $metawiki, $centralauth, $ul, $wp, $wikiUsername, $wikiPassword );

$c->addCentralAuthUsers( 'founder' );
$c->addCentralAuthUsers( 'steward' );
$c->addCentralAuthUsers( 'sysadmin' );
$c->addCentralAuthUsers( 'staff' );
$c->addCentralAuthUsers( 'global-interface-editor' );
$c->addCentralAuthUsers( 'global-sysop' );
$c->addCentralAuthUsers( 'ombuds' );

$c->addMetaUsers( 'wmf-supportsafety' );

$c->addEnwikiUsers( 'bureaucrat' );
$c->addEnwikiUsers( 'sysop' );
$c->addEnwikiUsers( 'patroller' ); // New Page Patroller
$c->addEnwikiUsers( 'bot' );
$c->addEnwikiUsers( 'checkuser' );
$c->addEnwikiUsers( 'suppress' ); // Oversighter

$c->addFormerAdmins();

$c->addEnwikiUsersByEditCount( 'extendedconfirmed', 500 );
$c->addEnwikiUsersByEditCount( '10k', 10000 );

$c->logIn();

$c->addUsersFromEnwikiJsonPage( 'arbcom', 'User:AmoryBot/crathighlighter.js/arbcom.json' );
$c->addUsersFromEnwikiJsonPage( 'productiveIPs', 'User:Novem_Linguae/User_lists/Productive_IPs.js' );

$c->writeUpdate();

// Close PDO connections.
unset( $enwiki );
unset( $metawiki );
unset( $centralauth );

View::print( "\nMission accomplished.\n\n" ); // extra line breaks at end for CLI
