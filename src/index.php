<?php

// This is a bot that automates this 10 step checklist for promoting Good Topics and Featured Topics:
// https://en.wikipedia.org/wiki/User:Aza24/FTC/Promote_Instructions

ini_set("display_errors", '1');
error_reporting(E_ALL);
assert_options(ASSERT_BAIL, true);

// test mode
$READ_ONLY_TEST_MODE = true;
$TEST_PAGES = [
	/*
	1 => [
		'nominationPageTitle' => 'Wikipedia:Featured and good topic candidates/1998–99 Manchester United F.C. season/archive1',
		'goodOrFeatured' => 'good',
	],
	*/
	2 => [
		'nominationPageTitle' => 'Wikipedia:Featured and good topic candidates/Tour Championship (snooker)/archive1',
		'goodOrFeatured' => 'featured',
	],
	/*
	3 => [
		'nominationPageTitle' => 'Wikipedia:Featured and good topic candidates/Dua Lipa (album)/archive1',
		'goodOrFeatured' => 'good',
	],
	*/
];

// constants
$MAX_PAGES_ALLOWED_IN_CATEGORY = 3;
$MAX_ARTICLES_ALLOWED_IN_TOPIC = 30;
$TRACKING_CATEGORY_NAME = 'Category:Good and featured topics to promote';
$SECONDS_BETWEEN_API_READS = 0; // https://www.mediawiki.org/wiki/API:Etiquette "Making your requests in series rather than in parallel, by waiting for one request to finish before sending a new request, should result in a safe request rate."
$SECONDS_BETWEEN_API_EDITS = 10; // https://en.wikipedia.org/wiki/Wikipedia:Bot_policy#Performance "Bots' editing speed should be regulated in some way; subject to approval, bots doing non-urgent tasks may edit approximately once every ten seconds, while bots doing more urgent tasks may edit approximately once every five seconds."
$ARTICLE_HISTORY_MAX_ACTIONS = 15; // just a guess
$SHORT_WIKICODE_IN_CONSOLE = true;
$CHARACTERS_TO_ECHO = 3000;
/*
$GOOD_TOPIC_TYPES = [];
$GOOD_TOPIC_TYPES_WITH_SUBPAGES = [];
$FEATURED_TOPIC_TYPES = [];
*/

require_once('bootstrap.php');

// Keep randos from running the bot in browser and in bash
if (
	($_GET['password'] ?? '') != $config['httpAndBashPassword'] &&
	($argv[1] ?? '') != $config['httpAndBashPassword']
) {
	die('Invalid password.');
}

// log in
echoAndFlush("PHP version: " . PHP_VERSION, 'variable');
$objwiki = new WikiAPIWrapper($config['wikiUsername'], $config['wikiPassword']);

// read tracking category

if ( $TEST_PAGES ) {
	$pagesToPromote = sql_make_list_from_sql_result_array($TEST_PAGES, 'nominationPageTitle');
	$message = 'In test mode. Using $TEST_PAGES variable.';
	$message .= "\n\n" . var_export($pagesToPromote, true);
	echoAndFlush($message, 'api_read');
} else {
	$pagesToPromote = $objwiki->categorymembers($TRACKING_CATEGORY_NAME);
}

// do some kind of authentication. whitelist, extended confirmed, etc.
// $whitelist = ['Novem Linguae', 'Aza24', 'GamerPro64', 'Sturmvogel 66'];
// maybe I have a userspace page that is extended confirmed protected, where people add bullets of pages for NovemBot to process?
// a good security feature is that a page needs to have the featured topic template for the bot to do anything, else the bot will not do any exponential editing

// check how many pages in tracking category. if too many, don't run. probably vandalism.
if ( count($pagesToPromote) > $MAX_PAGES_ALLOWED_IN_CATEGORY ) {
	logError('Too many categories. Possible vandalism?');
	die();
}

foreach ( $pagesToPromote as $key => $nominationPageTitle ) {
	echoAndFlush($nominationPageTitle, 'newtopic');
	try {
		// STEP A - READ PAGE CONTAINING {{User:NovemBot/Promote|type=good/featured}} ================
		$nominationPageWikicode = $objwiki->getpage($nominationPageTitle);
		abortIfAddToTopic($nominationPageWikicode, $nominationPageTitle);
		if ( $TEST_PAGES ) {
			$goodOrFeatured = sql_search_result_array_by_key1_and_return_key2($TEST_PAGES, 'nominationPageTitle', $nominationPageTitle, 'goodOrFeatured');
		} else {
			$novemBotTemplateWikicode = sliceNovemBotPromoteTemplate($nominationPageWikicode, $nominationPageTitle);
			$goodOrFeatured = getGoodOrFeaturedFromNovemBotTemplate($novemBotTemplateWikicode, $nominationPageTitle);
		}
		echoAndFlush($goodOrFeatured, 'variable');
		
		// STEP 2 - MAKE TOPIC PAGE ==================================================================
		$topicBoxWikicode = getTopicBoxWikicode($nominationPageWikicode, $nominationPageTitle);
		$mainArticleTitle = getMainArticleTitle($topicBoxWikicode, $nominationPageTitle);
		$topicDescriptionWikicode = getTopicDescriptionWikicode($nominationPageWikicode);
		$topicWikipediaPageTitle = getTopicWikipediaPageTitle($mainArticleTitle, $goodOrFeatured);
		$topicWikipediaPageWikicode = getTopicWikipediaPageWikicode($topicDescriptionWikicode, $topicBoxWikicode);
		$objwiki->edit($topicWikipediaPageTitle, $topicWikipediaPageWikicode);
		
		// STEP 3 - MAKE TOPIC TALK PAGE =============================================================
		$topicTalkPageTitle = getTopicTalkPageTitle($mainArticleTitle, $goodOrFeatured);
		$datetime = getDatetime();
		$allArticleTitles = getAllArticleTitles($topicBoxWikicode, $nominationPageTitle);
		$nonMainArticleTitles = getNonMainArticleTitles($allArticleTitles, $mainArticleTitle);
		$mainArticleTalkPageWikicode = $objwiki->getpage('Talk:'.$mainArticleTitle);
		$wikiProjectBanners = getWikiProjectBanners($mainArticleTalkPageWikicode, $mainArticleTitle);
		$topicTalkPageWikicode = getTopicTalkPageWikicode($mainArticleTitle, $nonMainArticleTitles, $goodOrFeatured, $datetime, $wikiProjectBanners, $nominationPageTitle);
		$objwiki->edit($topicTalkPageTitle, $topicTalkPageWikicode);
		
		// STEP 4 - UPDATE TALK PAGES OF ARTICLES ====================================================
		abortIfTooManyArticlesInTopic($allArticleTitles, $MAX_ARTICLES_ALLOWED_IN_TOPIC, $nominationPageTitle);
		foreach ( $allArticleTitles as $key => $articleTitle ) {
			$talkPageTitle = 'Talk:' . $articleTitle;
			$talkPageWikicode = $objwiki->getpage($talkPageTitle);
			$talkPageWikicode = addHeadingIfNeeded($talkPageWikicode, $talkPageTitle);
			$talkPageWikicode = removeGTCFTCTemplate($talkPageWikicode);
			$talkPageWikicode = addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
			$nextActionNumber = determineNextActionNumber($talkPageWikicode, $ARTICLE_HISTORY_MAX_ACTIONS, $talkPageTitle);
			$talkPageWikicode = updateArticleHistory($talkPageWikicode, $nextActionNumber, $goodOrFeatured, $datetime, $mainArticleTitle, $articleTitle, $talkPageTitle, $nominationPageTitle);
			$objwiki->edit($talkPageTitle, $talkPageWikicode);
		}
		
		// STEP 5 - UPDATE COUNT=====================================================================
		$countPageTitle = ( $goodOrFeatured == 'good' ) ? 'Wikipedia:Good topics/count' : 'Wikipedia:Featured topics/count';
		$countPageWikicode = $objwiki->getpage($countPageTitle);
		$articlesInTopic = count($allArticleTitles);
		$countPageWikicode = updateCountPageTopicCount($countPageWikicode, $countPageTitle);
		$countPageWikicode = updateCountPageArticleCount($countPageWikicode, $countPageTitle, $articlesInTopic);
		$objwiki->edit($countPageTitle, $countPageWikicode);
		
		// STEP 6 - ADD TO GOOD/FEATURED TOPIC PAGE ==================================================
		// GT's and FT's have different categories
		// some GT's have subpages
		// the |topic= in the {{article history}} template appears to only be for good articles?
		// https://en.wikipedia.org/wiki/Wikipedia:WikiProject_Good_articles/Project_quality_task_force#Good_article_topic_values
		/*
		$topicType = getTopicType($mainArticleTalkPageWikicode, $mainArticleTitle);
		$listPageTitle = getListPageTitle($topicType, $goodOrFeatured);
		$listPageWikicode = $objwiki->getpage($listPageTitle);
		$listPageWikicode = addTopicToListPage($listPageWikicode, $listPageTitle, $mainArticleTitle);
		$objwiki->edit($listPageTitle, $listPageWikicode);
		*/
		
		// STEP 7 - CREATE CHILD CATEGORIES =================================================
		$goodArticleCount = getGoodArticleCount($topicBoxWikicode);
		$featuredArticleCount = getFeaturedArticleCount($topicBoxWikicode);
		if ( $goodArticleCount + $featuredArticleCount <= 0 ) {
			throw new giveUpOnThisTopic("Unexpected value for the count of good articles and featured articles in the topic. Sum is 0 or less.");
		}
		if ( $goodArticleCount > 0 ) {
			$goodArticleCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle good content";
			$goodArticleCategoryWikitext = "[[Category:Wikipedia featured topics $mainArticleTitle]]";
			$objwiki->edit($goodArticleCategoryTitle, $goodArticleCategoryWikitext);
		}
		if ( $featuredArticleCount > 0 ) {
			$featuredArticleCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle featured content";
			$featuredArticleCategoryWikitext = "[[Category:Wikipedia featured topics $mainArticleTitle]]";
			$objwiki->edit($featuredArticleCategoryTitle, $featuredArticleCategoryWikitext);
		}
		
		// STEP 8 - CREATE PARENT CATEGORY ===========================================================
		$parentCategoryTitle = "Category:Wikipedia featured topics $mainArticleTitle";
		$parentCategoryWikitext = "[[Category:Wikipedia featured topics categories|$mainArticleTitle]]";
		$objwiki->edit($parentCategoryTitle, $parentCategoryWikitext);
		
		// STEP 9 - ADD TO LOG =======================================================================
		$logPageTitle = getLogPageTitle($datetime, $goodOrFeatured);
		$logPageWikicode = $objwiki->getpage($logPageTitle);
		$logPageWikicode = trim($logPageWikicode . "\n{{" . $nominationPageTitle . '}}');
		$objwiki->edit($logPageTitle, $logPageWikicode);
		
		// STEP 10 - ADD TO ANNOUNCEMENTS TEMPLATE ===================================================
		if ( $goodOrFeatured == 'featured' ) {
			// [[Template:Announcements/New featured content]]: add this article to top, remove 1 from the bottom
			$newFeaturedContentTitle = 'Template:Announcements/New featured content';
			$newFeaturedContentWikicode = $objwiki->getpage($newFeaturedContentTitle);
			$newFeaturedContentWikicode = addTopicToNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $mainArticleTitle);
			$newFeaturedContentWikicode = removeBottomTopicFromNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode);
			$objwiki->edit($newFeaturedContentTitle, $newFeaturedContentWikicode);
			
			// [[Wikipedia:Goings-on]]: add
			$goingsOnTitle = 'Wikipedia:Goings-on';
			$goingsOnWikicode = $objwiki->getpage($goingsOnTitle);
			$goingsOnWikicode = addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $mainArticleTitle);
			$objwiki->edit($goingsOnTitle, $goingsOnWikicode);
		}
		
		// STEP 1 - CLOSE THE NOMINATION =============================================================
		// Replace template invokation with Success. ~~~~ or Error. ~~~~
	} catch (giveUpOnThisTopic $e) {
		logError($e->getMessage());
	}
}

echoAndFlush('', 'complete');