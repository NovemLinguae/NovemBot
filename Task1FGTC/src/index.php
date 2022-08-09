<?php

// Things to refactor now that I'm looking at this a year later...
// TODO: each step should be a method in a class
// TODO: multiple edits to the same page should be combined into one method that returns the final wikicode
// TODO: public and private for Promote class methods

// This is a bot that automates this 10 step checklist for promoting Good Topics and Featured Topics:
// https://en.wikipedia.org/wiki/User:Aza24/FTC/Promote_Instructions

ini_set("display_errors", '1');
error_reporting(E_ALL);
assert_options(ASSERT_BAIL, true);
date_default_timezone_set('UTC');

set_time_limit(55 * 60); // 55 minutes

// test mode
$READ_ONLY_TEST_MODE = false;
$TEST_PAGES = [
	//"Wikipedia:Featured and good topic candidates/Hundred Years' War (1345–1347)/archive1"
]; // Make this array empty to pull from the list of pings instead.

// constants
$MAX_TOPICS_ALLOWED_IN_BOT_RUN = 7;
$MAX_ARTICLES_ALLOWED_IN_TOPIC = 200;
$TRACKING_CATEGORY_NAME = 'Category:Good and featured topics to promote';
$SECONDS_BETWEEN_API_READS = 0; // https://www.mediawiki.org/wiki/API:Etiquette "Making your requests in series rather than in parallel, by waiting for one request to finish before sending a new request, should result in a safe request rate."
$SECONDS_BETWEEN_API_EDITS = 10; // https://en.wikipedia.org/wiki/Wikipedia:Bot_policy#Performance "Bots' editing speed should be regulated in some way; subject to approval, bots doing non-urgent tasks may edit approximately once every ten seconds, while bots doing more urgent tasks may edit approximately once every five seconds."
$ARTICLE_HISTORY_MAX_ACTIONS = 50; // just a guess
$SHORT_WIKICODE_IN_CONSOLE = false; // Set to false to help with semi-automated editing (copy pasting from browser to Wikipedia). Set to true to make browser more readable during testing.
$CHARACTERS_TO_ECHO = 3000; // When $SHORT_WIKICODE_IN_CONSOLE is set to true, how many characters to display.
$SHOW_API_READS = false;

require_once('config.php');
require_once('bootstrap.php');

$h = new Helper();
$eh = new EchoHelper($h);
$p = new Promote($eh, $h);

// Randos can only run the bot in read only test mode
if (
	($_GET['password'] ?? '') != $config['httpAndBashPassword'] &&
	($argv[1] ?? '') != $config['httpAndBashPassword']
) {
	$READ_ONLY_TEST_MODE = true;
}

// log in
$eh->echoAndFlush("PHP version: " . PHP_VERSION, 'variable');
$wapi = new WikiAPIWrapper($config['wikiUsername'], $config['wikiPassword'], $eh);

if ( $READ_ONLY_TEST_MODE ) {
	$message = 'In read only test mode. Set $READ_ONLY_TEST_MODE to false, and provide an HTTP or CLI password, in order to exit read only test mode and have the bot make live edits.';
	$eh->echoAndFlush($message, 'message');
}

if ( $TEST_PAGES ) {
	$pagesToPromote = $TEST_PAGES;
	$message = 'Using $TEST_PAGES variable.';
	$message .= "\n\n" . var_export($pagesToPromote, true);
	$eh->echoAndFlush($message, 'message');
} else { // read pings
	// example: ["Novem Linguae", "GamerPro64", "Sturmvogel 66", "Aza24"]
	$whitelist = $wapi->getpage('User:Novem_Linguae/Scripts/NovemBotTask1Whitelist.js');
	$whitelist = json_decode($whitelist);

	$listOfPings = $wapi->getUnreadPings();
	$listOfPings = $listOfPings['query']['notifications']['list'];
	$pagesToPromote = [];
	foreach ( $listOfPings as $key => $value ) {
		// make sure pinger is on whitelist
		$pingSender = $value['agent']['name'];
		if ( in_array($pingSender, $whitelist) ) {
			// make sure pinging page has {{Featured topic box}}
			$pingTitle = $value['title']['full'];
			$archivePageWikicode = $wapi->getpage($pingTitle);
			$containsFeaturedTopicBox = false;
			try {
				$containsFeaturedTopicBox = $p->getTopicBoxWikicode($archivePageWikicode, $pingTitle);
			} catch (Exception $e) {}
			if ( $containsFeaturedTopicBox ) {
				// add to list of pages to promote
				$pagesToPromote[] = $pingTitle;
			}
		}
	}

	if ( ! $READ_ONLY_TEST_MODE ) {
		$wapi->markAllPingsRead();
	}
}

$eh->html_var_export($pagesToPromote, 'variable');

// check how many valid pings. if too many, don't run. probably vandalism.
if ( count($pagesToPromote) > $MAX_TOPICS_ALLOWED_IN_BOT_RUN ) {
	$eh->logError('Too many categories. Possible vandalism?');
	die();
}

foreach ( $pagesToPromote as $key => $nominationPageTitle ) {
	$eh->echoAndFlush($nominationPageTitle, 'newtopic');
	try {
		// STEP A - READ PAGE CONTAINING {{User:NovemBot/Promote}} =============
		$nominationPageWikicode = $wapi->getpage($nominationPageTitle);
		
		if ( ! $READ_ONLY_TEST_MODE ) {
			// not all pings from featured topic pages need to be acted on
			// silent error to prevent error spam
			try {
				$p->abortIfPromotionTemplateMissing($nominationPageWikicode, $nominationPageTitle);
			} catch (Exception $e) {
				$eh->logError('{{t|User:NovemBot/Promote}} template missing from page.');
				continue;
			}
		}
		
		// couple of checks
		$p->abortIfAddToTopic($nominationPageWikicode, $nominationPageTitle);
		$topicBoxWikicode = $p->getTopicBoxWikicode($nominationPageWikicode, $nominationPageTitle);
		$topicBoxWikicode = $p->setTopicBoxViewParameterToYes($topicBoxWikicode);
		$mainArticleTitle = $p->getMainArticleTitle($topicBoxWikicode, $nominationPageTitle);
		$topicTitle = $p->getTopicTitle($topicBoxWikicode, $mainArticleTitle);
		$topicBoxWikicode = $p->setTopicBoxTitleParameter($topicBoxWikicode, $mainArticleTitle);
		$topicBoxWikicode = $p->cleanTopicBoxTitleParameter($topicBoxWikicode);
		$allArticleTitles = $p->getAllArticleTitles($topicBoxWikicode, $nominationPageTitle);
		$goodArticleCount = $p->getGoodArticleCount($topicBoxWikicode);
		$featuredArticleCount = $p->getFeaturedArticleCount($topicBoxWikicode);
		$p->checkCounts($goodArticleCount, $featuredArticleCount, $allArticleTitles);
		
		// decide if good topic or featured topic
		$goodOrFeatured = $p->decideIfGoodOrFeatured($goodArticleCount, $featuredArticleCount);
		$eh->echoAndFlush($goodOrFeatured, 'variable');
		
		// STEP 2 - MAKE TOPIC PAGE ===============================================================
		$topicDescriptionWikicode = $p->getTopicDescriptionWikicode($nominationPageWikicode);
		$topicDescriptionWikicode = $p->removeSignaturesFromTopicDescription($topicDescriptionWikicode);
		$topicWikipediaPageTitle = $p->getTopicWikipediaPageTitle($topicTitle);
		$topicWikipediaPageWikicode = $p->getTopicWikipediaPageWikicode($topicDescriptionWikicode, $topicBoxWikicode);
		$wapi->edit($topicWikipediaPageTitle, $topicWikipediaPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured); // This is our first edit. Everything before here is read only (except for clearing unread pings)
		
		// STEP 3 - MAKE TOPIC TALK PAGE ==========================================================
		$topicTalkPageTitle = $p->getTopicTalkPageTitle($topicTitle);
		$datetime = $p->getDatetime();
		$nonMainArticleTitles = $p->getNonMainArticleTitles($allArticleTitles, $mainArticleTitle);
		$mainArticleTalkPageWikicode = $wapi->getpage('Talk:'.$mainArticleTitle);
		$wikiProjectBanners = $p->getWikiProjectBanners($mainArticleTalkPageWikicode, $topicTitle);
		$topicTalkPageWikicode = $p->makeTopicTalkPageWikicode($mainArticleTitle, $topicTitle, $nonMainArticleTitles, $goodOrFeatured, $datetime, $wikiProjectBanners, $nominationPageTitle);
		$wapi->edit($topicTalkPageTitle, $topicTalkPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		
		// STEP 4 - UPDATE TALK PAGES OF ARTICLES =================================================
		$p->abortIfTooManyArticlesInTopic($allArticleTitles, $MAX_ARTICLES_ALLOWED_IN_TOPIC, $nominationPageTitle);
		foreach ( $allArticleTitles as $key => $articleTitle ) {
			$talkPageTitle = 'Talk:' . $articleTitle;
			$talkPageWikicode = $wapi->getpage($talkPageTitle);
			// $talkPageWikicode = addHeadingIfNeeded($talkPageWikicode, $talkPageTitle);
			$talkPageWikicode = $p->removeGTCFTCTemplate($talkPageWikicode);
			$talkPageWikicode = $p->addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
			$nextActionNumber = $p->determineNextActionNumber($talkPageWikicode, $ARTICLE_HISTORY_MAX_ACTIONS, $talkPageTitle);
			$talkPageWikicode = $p->updateArticleHistory($talkPageWikicode, $nextActionNumber, $goodOrFeatured, $datetime, $mainArticleTitle, $topicTitle, $articleTitle, $talkPageTitle, $nominationPageTitle);
			$wapi->edit($talkPageTitle, $talkPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		}
		
		// STEP 5 - UPDATE COUNTS =================================================================
		$countPageTitle = ( $goodOrFeatured == 'good' ) ? 'Wikipedia:Good topics/count' : 'Wikipedia:Featured topics/count';
		$countPageWikicode = $wapi->getpage($countPageTitle);
		$articlesInTopic = count($allArticleTitles);
		$countPageWikicode = $p->updateCountPageTopicCount($countPageWikicode, $countPageTitle);
		$countPageWikicode = $p->updateCountPageArticleCount($countPageWikicode, $countPageTitle, $articlesInTopic);
		$wapi->edit($countPageTitle, $countPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);

		$countTemplateTitle = 'Template:Featured topic log';
		$countTemplateWikicode = $wapi->getpage($countTemplateTitle);
		$month = date('F');
		$year = date('Y');
		$countTemplateWikicode = $p->getTemplateFeaturedTopicLogWikicode($month, $year, $countTemplateWikicode, $goodOrFeatured);
		$wapi->edit($countTemplateTitle, $countTemplateWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		
		// STEP 6 - ADD TO GOOD/FEATURED TOPIC PAGE =============================================
		// Too complex. Human must do this.
		
		// STEP 7 - CREATE CHILD CATEGORIES =====================================================
		if ( $goodArticleCount > 0 ) {
			$goodArticleCategoryTitle = "Category:Wikipedia featured topics $topicTitle good content";
			$goodArticleCategoryWikitext = "[[Category:Wikipedia featured topics $topicTitle]]";
			$wapi->edit($goodArticleCategoryTitle, $goodArticleCategoryWikitext, $topicWikipediaPageTitle, $goodOrFeatured);
		}
		if ( $featuredArticleCount > 0 ) {
			$featuredArticleCategoryTitle = "Category:Wikipedia featured topics $topicTitle featured content";
			$featuredArticleCategoryWikitext = "[[Category:Wikipedia featured topics $topicTitle]]";
			$wapi->edit($featuredArticleCategoryTitle, $featuredArticleCategoryWikitext, $topicWikipediaPageTitle, $goodOrFeatured);
		}
		
		// STEP 8 - CREATE PARENT CATEGORY ========================================================
		$parentCategoryTitle = "Category:Wikipedia featured topics $topicTitle";
		$parentCategoryWikitext = "[[Category:Wikipedia featured topics categories|$topicTitle]]";
		$wapi->edit($parentCategoryTitle, $parentCategoryWikitext, $topicWikipediaPageTitle, $goodOrFeatured);
		
		// STEP 9 - ADD TO LOG ====================================================================
		$logPageTitle = $p->getLogPageTitle($datetime, $goodOrFeatured);
		$logPageWikicode = $wapi->getpage($logPageTitle);
		$logPageWikicode = trim($logPageWikicode . "\n{{" . $nominationPageTitle . '}}');
		$wapi->edit($logPageTitle, $logPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		
		// STEP 10 - ADD TO ANNOUNCEMENTS TEMPLATE ================================================
		if ( $goodOrFeatured == 'featured' ) {
			// [[Template:Announcements/New featured content]]: add this article to top, remove 1 from the bottom
			$newFeaturedContentTitle = 'Template:Announcements/New featured content';
			$newFeaturedContentWikicode = $wapi->getpage($newFeaturedContentTitle);
			$newFeaturedContentWikicode = $p->addTopicToNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $topicTitle);
			$newFeaturedContentWikicode = $p->removeBottomTopicFromNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode);
			$wapi->edit($newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
			
			// [[Wikipedia:Goings-on]]: add
			$goingsOnTitle = 'Wikipedia:Goings-on';
			$goingsOnWikicode = $wapi->getpage($goingsOnTitle);
			$timestamp = time();
			$goingsOnWikicode = $p->addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $topicTitle, $timestamp);
			$wapi->edit($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		}
		
		// STEP 11 - REMOVE FROM [[WP:FGTC]] =====================================================
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$fgtcWikicode = $wapi->getpage($fgtcTitle);
		$fgtcWikicode = $p->removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
		$wapi->edit($fgtcTitle, $fgtcWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		
		// REMINDER ABOUT STEP 6
		$eh->echoAndFlush("Step 6 must be done manually. Add {{{$topicWikipediaPageTitle}}} to the appropriate section of either [[Wikipedia:Featured topics]] or [[Wikipedia:Good topics]]", 'message');
		
		if ( ! $READ_ONLY_TEST_MODE ) {
			// STEP 1 - CLOSE THE NOMINATION =========================================================
			// Replace template invokation with Success. ~~~~ or Error. ~~~~
			// Also change {{User:NovemBot/Promote}} to include |done=yes, which will prevent the bot from going into an endless loop every hour.
			$nominationPageWikicode = $wapi->getpage($nominationPageTitle); // Fetch a fresh copy of the nomination page, to prevent edit conflicts.
			$nominationPageWikicode = $p->markDoneAndSuccessful($nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured);
			$wapi->edit($nominationPageTitle, $nominationPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		}
	} catch (GiveUpOnThisTopic $e) {
		$errorMessage = $e->getMessage();
		
		$eh->logError($errorMessage);
		
		// write error to /archive1 page
		$nominationPageWikicode = $wapi->getpage($nominationPageTitle);
		$nominationPageWikicode = $p->markError($nominationPageWikicode, $nominationPageTitle, $errorMessage);
		$editSummary = 'Log issue that prevented this topic from being promoted by the promotion bot. Ping [[User:Novem Linguae]]. (NovemBot Task 1)';
		$wapi->editSimple($nominationPageTitle, $nominationPageWikicode, $editSummary);
	}
}

$eh->echoAndFlush('', 'complete');