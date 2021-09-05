<?php

// This is a bot that automates this 10 step checklist for promoting Good Topics and Featured Topics:
// https://en.wikipedia.org/wiki/User:Aza24/FTC/Promote_Instructions

ini_set("display_errors", '1');
error_reporting(E_ALL);
assert_options(ASSERT_BAIL, true);
date_default_timezone_set('UTC');

// test mode
$READ_ONLY_TEST_MODE = true;
$TEST_PAGES = [
	// 'Wikipedia:Featured and good topic candidates/Burnley F.C./archive1'
]; // Make this array empty to pull from "Category:Good and featured topics to promote" instead. That's the tracking category for {{User:NovemBot/Promote}}.

// constants
$MAX_TOPICS_ALLOWED_IN_BOT_RUN = 7;
$MAX_ARTICLES_ALLOWED_IN_TOPIC = 50;
$TRACKING_CATEGORY_NAME = 'Category:Good and featured topics to promote';
$SECONDS_BETWEEN_API_READS = 0; // https://www.mediawiki.org/wiki/API:Etiquette "Making your requests in series rather than in parallel, by waiting for one request to finish before sending a new request, should result in a safe request rate."
$SECONDS_BETWEEN_API_EDITS = 10; // https://en.wikipedia.org/wiki/Wikipedia:Bot_policy#Performance "Bots' editing speed should be regulated in some way; subject to approval, bots doing non-urgent tasks may edit approximately once every ten seconds, while bots doing more urgent tasks may edit approximately once every five seconds."
$ARTICLE_HISTORY_MAX_ACTIONS = 15; // just a guess
$SHORT_WIKICODE_IN_CONSOLE = false; // Set to false to help with semi-automated editing (copy pasting from browser to Wikipedia). Set to true to make browser more readable during testing.
$CHARACTERS_TO_ECHO = 3000; // When $SHORT_WIKICODE_IN_CONSOLE is set to true, how many characters to display.

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

// read tracking category
if ( $TEST_PAGES ) {
	$pagesToPromote = $TEST_PAGES;
	$message = 'In test mode. Using $TEST_PAGES variable.';
	$message .= "\n\n" . var_export($pagesToPromote, true);
	$eh->echoAndFlush($message, 'api_read');
} else {
	$pagesToPromote = $wapi->categorymembers($TRACKING_CATEGORY_NAME);
}

// Remove Wikipedia:Wikipedia:Featured and good topic candidates from list of pages to process. Sometimes this page shows up in the category because it transcludes the nomination pages. We only want to process the nomination pages.
$pagesToPromote = $h->deleteArrayValue($pagesToPromote, 'Wikipedia:Featured and good topic candidates');
$eh->html_var_export($pagesToPromote, 'variable');

// check how many pages in tracking category. if too many, don't run. probably vandalism.
if ( count($pagesToPromote) > $MAX_TOPICS_ALLOWED_IN_BOT_RUN ) {
	$eh->logError('Too many categories. Possible vandalism?');
	die();
}

foreach ( $pagesToPromote as $key => $nominationPageTitle ) {
	$eh->echoAndFlush($nominationPageTitle, 'newtopic');
	try {
		// STEP A - READ PAGE CONTAINING {{User:NovemBot/Promote}} =============
		$nominationPageWikicode = $wapi->getpage($nominationPageTitle);
		
		$p->abortIfAddToTopic($nominationPageWikicode, $nominationPageTitle);
		
		// couple of checks
		$topicBoxWikicode = $p->getTopicBoxWikicode($nominationPageWikicode, $nominationPageTitle);
		$topicBoxWikicode = $p->setTopicBoxViewParamterToYes($topicBoxWikicode);
		$topicBoxWikicode = $p->cleanTopicBoxTitleParameter($topicBoxWikicode);
		$allArticleTitles = $p->getAllArticleTitles($topicBoxWikicode, $nominationPageTitle);
		$goodArticleCount = $p->getGoodArticleCount($topicBoxWikicode);
		$featuredArticleCount = $p->getFeaturedArticleCount($topicBoxWikicode);
		$p->checkCounts($goodArticleCount, $featuredArticleCount, $allArticleTitles);
		
		// decide if good topic or featured topic
		$goodOrFeatured = $p->decideIfGoodOrFeatured($goodArticleCount, $featuredArticleCount);
		$eh->echoAndFlush($goodOrFeatured, 'variable');
		
		// STEP 2 - MAKE TOPIC PAGE ===============================================================
		$mainArticleTitle = $p->getMainArticleTitle($topicBoxWikicode, $nominationPageTitle);
		$topicDescriptionWikicode = $p->getTopicDescriptionWikicode($nominationPageWikicode);
		$topicDescriptionWikicode = $p->removeSignaturesFromTopicDescription($topicDescriptionWikicode);
		$topicWikipediaPageTitle = $p->getTopicWikipediaPageTitle($mainArticleTitle, $goodOrFeatured);
		$topicWikipediaPageWikicode = $p->getTopicWikipediaPageWikicode($topicDescriptionWikicode, $topicBoxWikicode);
		$wapi->edit($topicWikipediaPageTitle, $topicWikipediaPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		
		// STEP 3 - MAKE TOPIC TALK PAGE ==========================================================
		$topicTalkPageTitle = $p->getTopicTalkPageTitle($mainArticleTitle);
		$datetime = $p->getDatetime();
		$nonMainArticleTitles = $p->getNonMainArticleTitles($allArticleTitles, $mainArticleTitle);
		$mainArticleTalkPageWikicode = $wapi->getpage('Talk:'.$mainArticleTitle);
		$wikiProjectBanners = $p->getWikiProjectBanners($mainArticleTalkPageWikicode, $mainArticleTitle);
		$topicTalkPageWikicode = $p->makeTopicTalkPageWikicode($mainArticleTitle, $nonMainArticleTitles, $goodOrFeatured, $datetime, $wikiProjectBanners, $nominationPageTitle);
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
			$talkPageWikicode = $p->updateArticleHistory($talkPageWikicode, $nextActionNumber, $goodOrFeatured, $datetime, $mainArticleTitle, $articleTitle, $talkPageTitle, $nominationPageTitle);
			$wapi->edit($talkPageTitle, $talkPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		}
		
		// STEP 5 - UPDATE COUNT==================================================================
		$countPageTitle = ( $goodOrFeatured == 'good' ) ? 'Wikipedia:Good topics/count' : 'Wikipedia:Featured topics/count';
		$countPageWikicode = $wapi->getpage($countPageTitle);
		$articlesInTopic = count($allArticleTitles);
		$countPageWikicode = $p->updateCountPageTopicCount($countPageWikicode, $countPageTitle);
		$countPageWikicode = $p->updateCountPageArticleCount($countPageWikicode, $countPageTitle, $articlesInTopic);
		$wapi->edit($countPageTitle, $countPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		
		// STEP 6 - ADD TO GOOD/FEATURED TOPIC PAGE =============================================
		// Too complex. Human must do this.
		
		// STEP 7 - CREATE CHILD CATEGORIES =====================================================
		if ( $goodArticleCount > 0 ) {
			$goodArticleCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle good content";
			$goodArticleCategoryWikitext = "[[Category:Wikipedia featured topics $mainArticleTitle]]";
			$wapi->edit($goodArticleCategoryTitle, $goodArticleCategoryWikitext, $topicWikipediaPageTitle, $goodOrFeatured);
		}
		if ( $featuredArticleCount > 0 ) {
			$featuredArticleCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle featured content";
			$featuredArticleCategoryWikitext = "[[Category:Wikipedia featured topics $mainArticleTitle]]";
			$wapi->edit($featuredArticleCategoryTitle, $featuredArticleCategoryWikitext, $topicWikipediaPageTitle, $goodOrFeatured);
		}
		
		// STEP 8 - CREATE PARENT CATEGORY ========================================================
		$parentCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle";
		$parentCategoryWikitext = "[[Category:Wikipedia featured topics categories|$mainArticleTitle]]";
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
			$newFeaturedContentWikicode = $p->addTopicToNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $mainArticleTitle);
			$newFeaturedContentWikicode = $p->removeBottomTopicFromNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode);
			$wapi->edit($newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
			
			// [[Wikipedia:Goings-on]]: add
			$goingsOnTitle = 'Wikipedia:Goings-on';
			$goingsOnWikicode = $wapi->getpage($goingsOnTitle);
			$timestamp = time();
			$goingsOnWikicode = $p->addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $mainArticleTitle, $timestamp);
			$wapi->edit($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		}
		
		// STEP 11 - REMOVE FROM [[WP:FGTC]] =====================================================
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$fgtcWikicode = $wapi->getpage($fgtcTitle);
		$fgtcWikicode = $p->removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
		$wapi->edit($fgtcTitle, $fgtcWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
		
		// STEP 1 - CLOSE THE NOMINATION =========================================================
		// Replace template invokation with Success. ~~~~ or Error. ~~~~
		// Also change {{User:NovemBot/Promote}} to include |done=yes, which will take the page out of the tracking category.
		$nominationPageWikicode = $wapi->getpage($nominationPageTitle); // Fetch a fresh copy of the nomination page, to prevent edit conflicts.
		$nominationPageWikicode = $p->markDoneAndSuccessful($nominationPageWikicode, $nominationPageTitle);
		$wapi->edit($nominationPageTitle, $nominationPageWikicode, $topicWikipediaPageTitle, $goodOrFeatured);
	} catch (GiveUpOnThisTopic $e) {
		$eh->logError($e->getMessage());
	}
}

$eh->echoAndFlush('', 'complete');