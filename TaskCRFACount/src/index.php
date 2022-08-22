<?php

require_once('config.php');
require_once('botclasses.php');
require_once('EchoHelper.php');
require_once('Helper.php');
require_once('RFACount.php');
require_once('WikiAPIWrapper.php');

$SECONDS_BETWEEN_API_READS = 0; // https://www.mediawiki.org/wiki/API:Etiquette "Making your requests in series rather than in parallel, by waiting for one request to finish before sending a new request, should result in a safe request rate."
$SECONDS_BETWEEN_API_EDITS = 10; // https://en.wikipedia.org/wiki/Wikipedia:Bot_policy#Performance "Bots' editing speed should be regulated in some way; subject to approval, bots doing non-urgent tasks may edit approximately once every ten seconds, while bots doing more urgent tasks may edit approximately once every five seconds."
$SHORT_WIKICODE_IN_CONSOLE = false; // Set to false to help with semi-automated editing (copy pasting from browser to Wikipedia). Set to true to make browser more readable during testing.
$CHARACTERS_TO_ECHO = 3000; // When $SHORT_WIKICODE_IN_CONSOLE is set to true, how many characters to display.
$SHOW_API_READS = true;

ini_set("display_errors", '1');
error_reporting(E_ALL);
assert_options(ASSERT_BAIL, true);
date_default_timezone_set('UTC');

// Make sure randos can't run the bot
if (
	($_GET['password'] ?? '') != $config['httpAndBashPassword'] &&
	($argv[1] ?? '') != $config['httpAndBashPassword']
) {
	die('?password= or CLI password required');
}

$h = new Helper();
$eh = new EchoHelper($h);
$wapi = new WikiAPIWrapper($config['wikiUsername'], $config['wikiPassword'], $eh);

$eh->echoAndFlush("PHP version: " . PHP_VERSION, 'variable');

$rfaPageWikitext = $wapi->getpage('Wikipedia:Requests for adminship');

$count = countRFAs($rfaPageWikitext);

$editSummary = "set RFA count to $count (NovemBot Task C)";
$wapi->edit('User:Amalthea/RfX/RfA count', $count, $editSummary);
//$wapi->edit('User:NovemBot/test', $count, $editSummary);

$eh->echoAndFlush('', 'complete');
