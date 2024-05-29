<?php

// Things to refactor now that I'm looking at this a year later...
// TODO: multiple edits to the same page should be combined into one method that returns the final wikicode
// TODO: public and private for Promote class methods

// This is a bot that automates this 10 step checklist for promoting Good Topics and Featured Topics:
// https://en.wikipedia.org/wiki/User:Aza24/FTC/Promote_Instructions

ini_set( "display_errors", '1' );
error_reporting( E_ALL );
date_default_timezone_set( 'UTC' );

// 55 minutes
set_time_limit( 55 * 60 );

// constants
$MAX_TOPICS_ALLOWED_IN_BOT_RUN = 7;
$MAX_ARTICLES_ALLOWED_IN_TOPIC = 200;
$TRACKING_CATEGORY_NAME = 'Category:Good and featured topics to promote';
// https://www.mediawiki.org/wiki/API:Etiquette "Making your requests in series rather than in parallel, by waiting for one request to finish before sending a new request, should result in a safe request rate."
$SECONDS_BETWEEN_API_READS = 0;
// https://en.wikipedia.org/wiki/Wikipedia:Bot_policy#Performance "Bots' editing speed should be regulated in some way; subject to approval, bots doing non-urgent tasks may edit approximately once every ten seconds, while bots doing more urgent tasks may edit approximately once every five seconds."
$SECONDS_BETWEEN_API_EDITS = 10;
// just a guess
$ARTICLE_HISTORY_MAX_ACTIONS = 50;
// Set to false to help with semi-automated editing (copy pasting from browser to Wikipedia). Set to true to make browser more readable during testing.
$SHORT_WIKICODE_IN_CONSOLE = false;
// When $SHORT_WIKICODE_IN_CONSOLE is set to true, how many characters to display.
$CHARACTERS_TO_ECHO = 3000;
$SHOW_API_READS = false;

// test defaults
// can override these defaults in the config file. putting it in the config file, which I usually skip deploying, reduces chances of accidentally deploying it, which I've done before and has caused bugs
$READ_ONLY_TEST_MODE = false;
$TEST_PAGES = [];

require_once 'config.php';
require_once 'bootstrap.php';

$h = new Helper();
$eh = new EchoHelper( $h );
$p = new Promote( $eh, $h );

// Randos can only run the bot in read only test mode
if (
	( $_GET['password'] ?? '' ) != $config['httpAndBashPassword'] &&
	( $argv[1] ?? '' ) != $config['httpAndBashPassword']
) {
	$READ_ONLY_TEST_MODE = true;
}

// log in
$eh->echoAndFlush( "PHP version: " . PHP_VERSION, 'variable' );
$wapi = new WikiAPIWrapper(
	$config['wikiUsername'],
	$config['wikiPassword'],
	$eh,
	$READ_ONLY_TEST_MODE,
	$SECONDS_BETWEEN_API_READS,
	$SECONDS_BETWEEN_API_EDITS,
	$h
);

if ( $READ_ONLY_TEST_MODE ) {
	$message = 'In read only test mode. Set $READ_ONLY_TEST_MODE to false, and provide an HTTP or CLI password, in order to exit read only test mode and have the bot make live edits.';
	$eh->echoAndFlush( $message, 'message' );
}

if ( $TEST_PAGES ) {
	$pagesToPromote = $TEST_PAGES;
	$message = 'Using $TEST_PAGES variable.';
	$message .= "\n\n" . var_export( $pagesToPromote, true );
	$eh->echoAndFlush( $message, 'message' );
// read pings
} else {
	// example: ["Novem Linguae", "GamerPro64", "Sturmvogel 66", "Aza24"]
	$allowlist = $wapi->getpage( 'User:Novem_Linguae/Scripts/NovemBotTask1Allowlist.js' );
	$allowlist = json_decode( $allowlist );

	$listOfPings = $wapi->getUnreadPings();
	$listOfPings = $listOfPings['query']['notifications']['list'];
	$pagesToPromote = [];
	// this will detect any unread ping, both red/new ones and gray/old ones
	foreach ( $listOfPings as $key => $value ) {
		// make sure pinger is on allow list
		$pingSender = $value['agent']['name'];
		if ( in_array( $pingSender, $allowlist ) ) {
			// make sure pinging page has {{Featured topic box}}
			$pingTitle = $value['title']['full'];
			$archivePageWikicode = $wapi->getpage( $pingTitle );
			$containsFeaturedTopicBox = false;
			try {
				$containsFeaturedTopicBox = $p->getTopicBoxWikicode( $archivePageWikicode, $pingTitle );
			} catch ( Exception $e ) {
				// do nothing
			}
			if ( $containsFeaturedTopicBox ) {
				// add to list of pages to promote
				$pagesToPromote[] = $pingTitle;
			}
		}
	}

	if ( !$READ_ONLY_TEST_MODE ) {
		$wapi->markAllPingsRead();
	}
}

$eh->html_var_export( $pagesToPromote, 'variable' );

// check how many valid pings. if too many, don't run. probably vandalism.
if ( count( $pagesToPromote ) > $MAX_TOPICS_ALLOWED_IN_BOT_RUN ) {
	$eh->logError( 'Too many categories. Possible vandalism?' );
	die();
}

$fgtc = new FGTCSteps(
	$p,
	$eh,
	$wapi,
	$READ_ONLY_TEST_MODE,
	$MAX_ARTICLES_ALLOWED_IN_TOPIC,
	$ARTICLE_HISTORY_MAX_ACTIONS
);
$fgtc->execute( $pagesToPromote );

$eh->echoAndFlush( '', 'complete' );
