<?php

// This is a bot that automates this 10 step checklist for promoting Good Topics and Featured Topics:
// https://en.wikipedia.org/wiki/User:Aza24/FTC/Promote_Instructions

ini_set("display_errors", '1');
error_reporting(E_ALL);
assert_options(ASSERT_BAIL, true);

// test mode
$READ_ONLY_TEST_MODE = true;
$TEST_PAGES = [
	1 => [
		'nominationPageTitle' => 'Wikipedia:Featured and good topic candidates/Tour Championship (snooker)/archive1',
		'goodOrFeatured' => 'featured',
	],
];

// constants
$MAX_TOPICS_ALLOWED_IN_BOT_RUN = 3;
$MAX_ARTICLES_ALLOWED_IN_TOPIC = 50;
$TRACKING_CATEGORY_NAME = 'Category:Good and featured topics to promote';
$SECONDS_BETWEEN_API_READS = 0; // https://www.mediawiki.org/wiki/API:Etiquette "Making your requests in series rather than in parallel, by waiting for one request to finish before sending a new request, should result in a safe request rate."
$SECONDS_BETWEEN_API_EDITS = 10; // https://en.wikipedia.org/wiki/Wikipedia:Bot_policy#Performance "Bots' editing speed should be regulated in some way; subject to approval, bots doing non-urgent tasks may edit approximately once every ten seconds, while bots doing more urgent tasks may edit approximately once every five seconds."
$ARTICLE_HISTORY_MAX_ACTIONS = 15; // just a guess
$SHORT_WIKICODE_IN_CONSOLE = false; // Set to false to help with semi-automated editing (copy pasting from browser to Wikipedia). Set to true to make browser more readable during testing.
$CHARACTERS_TO_ECHO = 3000; // When $SHORT_WIKICODE_IN_CONSOLE is set to true, how many characters to display.
/*
$GOOD_TOPIC_TYPES = [];
$GOOD_TOPIC_TYPES_WITH_SUBPAGES = [];
$FEATURED_TOPIC_TYPES = [];
*/

require_once('bootstrap.php');

$sh = new StringHelper();
$eh = new EchoHelper($sh);
$p = new Promote($eh, $sh);

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
	$pagesToPromote = $sh->sql_make_list_from_sql_result_array($TEST_PAGES, 'nominationPageTitle');
	$message = 'In test mode. Using $TEST_PAGES variable.';
	$message .= "\n\n" . var_export($pagesToPromote, true);
	$eh->echoAndFlush($message, 'api_read');
} else {
	$pagesToPromote = $wapi->categorymembers($TRACKING_CATEGORY_NAME);
}

// do some kind of authentication. whitelist, extended confirmed, etc.
// $whitelist = ['Novem Linguae', 'Aza24', 'GamerPro64', 'Sturmvogel 66'];
// maybe I have a userspace page that is extended confirmed protected, where people add bullets of pages for NovemBot to process?
// a good security feature is that a page needs to have the featured topic template for the bot to do anything, else the bot will not do any exponential editing

// check how many pages in tracking category. if too many, don't run. probably vandalism.
if ( count($pagesToPromote) > $MAX_TOPICS_ALLOWED_IN_BOT_RUN ) {
	$eh->logError('Too many categories. Possible vandalism?');
	die();
}

foreach ( $pagesToPromote as $key => $nominationPageTitle ) {
	$eh->echoAndFlush($nominationPageTitle, 'newtopic');
	try {
		// STEP A - READ PAGE CONTAINING {{User:NovemBot/Promote|type=good/featured}} ================
		$nominationPageWikicode = $wapi->getpage($nominationPageTitle);
		$p->abortIfAddToTopic($nominationPageWikicode, $nominationPageTitle);
		if ( $TEST_PAGES ) {
			$goodOrFeatured = $sh->sql_search_result_array_by_key1_and_return_key2($TEST_PAGES, 'nominationPageTitle', $nominationPageTitle, 'goodOrFeatured');
		} else {
			$novemBotTemplateWikicode = $p->sliceNovemBotPromoteTemplate($nominationPageWikicode, $nominationPageTitle);
			$goodOrFeatured = $p->getGoodOrFeaturedFromNovemBotTemplate($novemBotTemplateWikicode, $nominationPageTitle);
		}
		$eh->echoAndFlush($goodOrFeatured, 'variable');
		
		// STEP 2 - MAKE TOPIC PAGE ==================================================================
		$topicBoxWikicode = $p->getTopicBoxWikicode($nominationPageWikicode, $nominationPageTitle);
		$topicBoxWikicode = $p->setTopicBoxViewParamterToYes($topicBoxWikicode);
		$topicBoxWikicode = $p->cleanTopicBoxTitleParameter($topicBoxWikicode);
		$mainArticleTitle = $p->getMainArticleTitle($topicBoxWikicode, $nominationPageTitle);
		$topicDescriptionWikicode = $p->getTopicDescriptionWikicode($nominationPageWikicode);
		$topicDescriptionWikicode = $p->removeSignaturesFromTopicDescription($topicDescriptionWikicode);
		$topicWikipediaPageTitle = $p->getTopicWikipediaPageTitle($mainArticleTitle, $goodOrFeatured);
		$topicWikipediaPageWikicode = $p->getTopicWikipediaPageWikicode($topicDescriptionWikicode, $topicBoxWikicode);
		$wapi->edit($topicWikipediaPageTitle, $topicWikipediaPageWikicode);
		
		// STEP 3 - MAKE TOPIC TALK PAGE =============================================================
		$topicTalkPageTitle = $p->getTopicTalkPageTitle($mainArticleTitle, $goodOrFeatured);
		$datetime = $p->getDatetime();
		$allArticleTitles = $p->getAllArticleTitles($topicBoxWikicode, $nominationPageTitle);
		$nonMainArticleTitles = $p->getNonMainArticleTitles($allArticleTitles, $mainArticleTitle);
		$mainArticleTalkPageWikicode = $wapi->getpage('Talk:'.$mainArticleTitle);
		$wikiProjectBanners = $p->getWikiProjectBanners($mainArticleTalkPageWikicode, $mainArticleTitle);
		$topicTalkPageWikicode = $p->getTopicTalkPageWikicode($mainArticleTitle, $nonMainArticleTitles, $goodOrFeatured, $datetime, $wikiProjectBanners, $nominationPageTitle);
		$wapi->edit($topicTalkPageTitle, $topicTalkPageWikicode);
		
		// STEP 4 - UPDATE TALK PAGES OF ARTICLES ====================================================
		$p->abortIfTooManyArticlesInTopic($allArticleTitles, $MAX_ARTICLES_ALLOWED_IN_TOPIC, $nominationPageTitle);
		foreach ( $allArticleTitles as $key => $articleTitle ) {
			$talkPageTitle = 'Talk:' . $articleTitle;
			$talkPageWikicode = $wapi->getpage($talkPageTitle);
			// $talkPageWikicode = addHeadingIfNeeded($talkPageWikicode, $talkPageTitle);
			$talkPageWikicode = $p->removeGTCFTCTemplate($talkPageWikicode);
			$talkPageWikicode = $p->addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
			$nextActionNumber = $p->determineNextActionNumber($talkPageWikicode, $ARTICLE_HISTORY_MAX_ACTIONS, $talkPageTitle);
			$talkPageWikicode = $p->updateArticleHistory($talkPageWikicode, $nextActionNumber, $goodOrFeatured, $datetime, $mainArticleTitle, $articleTitle, $talkPageTitle, $nominationPageTitle);
			$wapi->edit($talkPageTitle, $talkPageWikicode);
		}
		
		// STEP 5 - UPDATE COUNT=====================================================================
		$countPageTitle = ( $goodOrFeatured == 'good' ) ? 'Wikipedia:Good topics/count' : 'Wikipedia:Featured topics/count';
		$countPageWikicode = $wapi->getpage($countPageTitle);
		$articlesInTopic = count($allArticleTitles);
		$countPageWikicode = $p->updateCountPageTopicCount($countPageWikicode, $countPageTitle);
		$countPageWikicode = $p->updateCountPageArticleCount($countPageWikicode, $countPageTitle, $articlesInTopic);
		$wapi->edit($countPageTitle, $countPageWikicode);
		
		// STEP 6 - ADD TO GOOD/FEATURED TOPIC PAGE ==================================================
		// Too complex. Human must do this.
		
		// STEP 7 - CREATE CHILD CATEGORIES =================================================
		$goodArticleCount = $p->getGoodArticleCount($topicBoxWikicode);
		$featuredArticleCount = $p->getFeaturedArticleCount($topicBoxWikicode);
		if ( $goodArticleCount + $featuredArticleCount <= 0 ) {
			throw new GiveUpOnThisTopic("Unexpected value for the count of good articles and featured articles in the topic. Sum is 0 or less.");
		}
		if ( $goodArticleCount > 0 ) {
			$goodArticleCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle good content";
			$goodArticleCategoryWikitext = "[[Category:Wikipedia featured topics $mainArticleTitle]]";
			$wapi->edit($goodArticleCategoryTitle, $goodArticleCategoryWikitext);
		}
		if ( $featuredArticleCount > 0 ) {
			$featuredArticleCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle featured content";
			$featuredArticleCategoryWikitext = "[[Category:Wikipedia featured topics $mainArticleTitle]]";
			$wapi->edit($featuredArticleCategoryTitle, $featuredArticleCategoryWikitext);
		}
		
		// STEP 8 - CREATE PARENT CATEGORY ===========================================================
		$parentCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle";
		$parentCategoryWikitext = "[[Category:Wikipedia featured topics categories|$mainArticleTitle]]";
		$wapi->edit($parentCategoryTitle, $parentCategoryWikitext);
		
		// STEP 9 - ADD TO LOG =======================================================================
		$logPageTitle = $p->getLogPageTitle($datetime, $goodOrFeatured);
		$logPageWikicode = $wapi->getpage($logPageTitle);
		$logPageWikicode = trim($logPageWikicode . "\n{{" . $nominationPageTitle . '}}');
		$wapi->edit($logPageTitle, $logPageWikicode);
		
		// STEP 10 - ADD TO ANNOUNCEMENTS TEMPLATE ===================================================
		if ( $goodOrFeatured == 'featured' ) {
			// [[Template:Announcements/New featured content]]: add this article to top, remove 1 from the bottom
			$newFeaturedContentTitle = 'Template:Announcements/New featured content';
			$newFeaturedContentWikicode = $wapi->getpage($newFeaturedContentTitle);
			$newFeaturedContentWikicode = $p->addTopicToNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $mainArticleTitle);
			$newFeaturedContentWikicode = $p->removeBottomTopicFromNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode);
			$wapi->edit($newFeaturedContentTitle, $newFeaturedContentWikicode);
			
			// [[Wikipedia:Goings-on]]: add
			$goingsOnTitle = 'Wikipedia:Goings-on';
			$goingsOnWikicode = $wapi->getpage($goingsOnTitle);
			$goingsOnWikicode = $p->addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $mainArticleTitle);
			$wapi->edit($goingsOnTitle, $goingsOnWikicode);
		}
		
		// STEP 11 - REMOVE FROM [[WP:FGTC]] ===================================================
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$fgtcWikicode = $wapi->getpage($fgtcTitle);
		$fgtcWikicode = $p->removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
		$wapi->edit($fgtcTitle, $fgtcWikicode);
		
		// STEP 1 - CLOSE THE NOMINATION =============================================================
		// Replace template invokation with Success. ~~~~ or Error. ~~~~
		// Also change {{User:NovemBot/Promote}} to include |done=yes, which will take the page out of the tracking category.
		/*
		$nominationPageWikicode = writeSuccessOrError($nominationPageWikicode, $nominationPageTitle);
		$wapi->edit($nominationPageTitle, $nominationPageWikicode);
		*/
	} catch (GiveUpOnThisTopic $e) {
		$eh->logError($e->getMessage());
	}
}

$eh->echoAndFlush('', 'complete');