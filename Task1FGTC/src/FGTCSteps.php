<?php

class FGTCSteps {
	protected $p;
	protected $eh;
	protected $wapi;
	protected $READ_ONLY_TEST_MODE;
	protected $MAX_ARTICLES_ALLOWED_IN_TOPIC;
	protected $ARTICLE_HISTORY_MAX_ACTIONS;
	protected $logPageTitle;
	protected $nominationPageTitle;
	protected $goodOrFeatured;
	protected $logPageWikicode;
	protected $topicWikipediaPageTitle;
	protected $nominationPageWikicode;
	protected $topicBoxWikicode;
	protected $mainArticleTitle;
	protected $topicTitle;
	protected $allArticleTitles;
	protected $goodArticleCount;
	protected $featuredArticleCount;
	protected $datetime;

	public function __construct(
		Promote $p,
		EchoHelper $eh,
		WikiAPIWrapper $wapi,
		bool $READ_ONLY_TEST_MODE,
		int $MAX_ARTICLES_ALLOWED_IN_TOPIC,
		int $ARTICLE_HISTORY_MAX_ACTIONS
	) {
		$this->p = $p;
		$this->eh = $eh;
		$this->wapi = $wapi;
		$this->READ_ONLY_TEST_MODE = $READ_ONLY_TEST_MODE;
		$this->MAX_ARTICLES_ALLOWED_IN_TOPIC = $MAX_ARTICLES_ALLOWED_IN_TOPIC;
		$this->ARTICLE_HISTORY_MAX_ACTIONS = $ARTICLE_HISTORY_MAX_ACTIONS;
		$this->logPageTitle = 'User:NovemBot/Task1Log';
	}

	public function execute($pagesToPromote) {
		foreach ( $pagesToPromote as $key => $this->nominationPageTitle ) {
			// First pass that is read only, to check for errors.
			$this->wapi->setReadOnlyMode(true);
			try {
				$this->doSteps();
			} catch (GiveUpOnThisTopic $e) {
				// If error, switch back to write mode so we can write the error message.
				$this->wapi->setReadOnlyMode($this->READ_ONLY_TEST_MODE);
				$this->handleError($e);
				continue;
			}

			// Second pass that edits pages (if not in test mode)
			$this->wapi->setReadOnlyMode($this->READ_ONLY_TEST_MODE);
			try {
				$this->doSteps();
			} catch (GiveUpOnThisTopic $e) {
				$this->handleError($e);
			}
		}
	}

	private function doSteps() {
		$this->wapi->setEditCountToZero();
		$this->eh->echoAndFlush($this->nominationPageTitle, 'newtopic');
		$this->readArchivePageAndSetVariables();
		$this->makeTopicPage();
		$this->makeTopicTalkPage();
		$this->updateTalkPagesOfArticles();
		$this->updateCountPage();
		$this->updateTemplateFeaturedTopicLog();
		$this->createChildCategories();
		$this->createParentCategory();
		$this->addToFGTCLog();
		if ( $this->goodOrFeatured == 'featured' ) {
			$this->addToTemplateAnnouncements();
			$this->addToWikipediaGoingsOn();
		}
		$this->removeFromFGTC();
		if ( ! $this->READ_ONLY_TEST_MODE ) { // This check prevents test topics from throwing a "promote template not found" error, which is distracting.
			$this->writeMessageOnArchivePage();
		}
		$this->addToUserspaceLog();
		$this->printConsoleReminderAboutStep6(); // Also a visual aid to easily see where the end of the run is.
	}

	private function addToUserspaceLog() {
		$editCount = $this->wapi->getEditCount();
		$mostRecentRevisionTimestamp = $this->wapi->getMostRecentRevisionTimestamp();
		$diffURL = "https://en.wikipedia.org/w/index.php?title=Special:Contributions&offset=$mostRecentRevisionTimestamp&target=NovemBot&limit=$editCount";

		$this->logPageWikicode = $this->wapi->getpage($this->logPageTitle);
		$this->logPageWikicode .= "\n* [[$this->nominationPageTitle]] - ~~~~~ - [$diffURL Diffs]";
		$this->wapi->edit($this->logPageTitle, $this->logPageWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step A
	  */
	private function readArchivePageAndSetVariables() {
		$this->nominationPageWikicode = $this->wapi->getpage($this->nominationPageTitle);

		if ( ! $this->READ_ONLY_TEST_MODE ) {
			// not all pings from featured topic pages need to be acted on
			// silent error to prevent error spam
			try {
				$this->p->abortIfPromotionTemplateMissingOrDone($this->nominationPageWikicode, $this->nominationPageTitle);
			} catch (Exception $e) {
				$this->eh->logError('{{t|User:NovemBot/Promote}} template missing from page.');
				throw new GiveUpOnThisTopic('{{t|User:NovemBot/Promote}} template missing from page.');
			}
		}

		// couple of checks
		$this->p->abortIfAddToTopic($this->nominationPageWikicode, $this->nominationPageTitle);
		$this->topicBoxWikicode = $this->p->getTopicBoxWikicode($this->nominationPageWikicode, $this->nominationPageTitle);
		$this->topicBoxWikicode = $this->p->setTopicBoxViewParameterToYes($this->topicBoxWikicode);
		$this->mainArticleTitle = $this->p->getMainArticleTitle($this->topicBoxWikicode, $this->nominationPageTitle);
		$this->topicTitle = $this->p->getTopicTitle($this->topicBoxWikicode, $this->mainArticleTitle);
		$this->topicBoxWikicode = $this->p->setTopicBoxTitleParameter($this->topicBoxWikicode, $this->mainArticleTitle);
		$this->topicBoxWikicode = $this->p->cleanTopicBoxTitleParameter($this->topicBoxWikicode);
		$this->allArticleTitles = $this->p->getAllArticleTitles($this->topicBoxWikicode, $this->nominationPageTitle);
		$this->goodArticleCount = $this->p->getGoodArticleCount($this->topicBoxWikicode);
		$this->featuredArticleCount = $this->p->getFeaturedArticleCount($this->topicBoxWikicode);
		$this->p->checkCounts($this->goodArticleCount, $this->featuredArticleCount, $this->allArticleTitles);

		// decide if good topic or featured topic
		$this->goodOrFeatured = $this->p->decideIfGoodOrFeatured($this->goodArticleCount, $this->featuredArticleCount);
		$this->eh->echoAndFlush($this->goodOrFeatured, 'variable');
	}

	/**
	  * Step 2
	  */
	private function makeTopicPage() {
		$topicDescriptionWikicode = $this->p->getTopicDescriptionWikicode($this->nominationPageWikicode);
		$topicDescriptionWikicode = $this->p->removeSignaturesFromTopicDescription($topicDescriptionWikicode);
		$this->topicWikipediaPageTitle = $this->p->getTopicWikipediaPageTitle($this->topicTitle);
		$topicWikipediaPageWikicode = $this->p->getTopicWikipediaPageWikicode($topicDescriptionWikicode, $this->topicBoxWikicode);
		$this->wapi->edit($this->topicWikipediaPageTitle, $topicWikipediaPageWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured); // This is our first edit. Everything before here is read only (except for clearing unread pings)
	}

	/**
	  * Step 3
	  */
	private function makeTopicTalkPage() {
		$topicTalkPageTitle = $this->p->getTopicTalkPageTitle($this->topicTitle);
		$this->datetime = $this->p->getDatetime();
		$nonMainArticleTitles = $this->p->getNonMainArticleTitles($this->allArticleTitles, $this->mainArticleTitle);
		$mainArticleTalkPageWikicode = $this->wapi->getpage('Talk:'.$this->mainArticleTitle);
		$wikiProjectBanners = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $this->topicTitle);
		$topicTalkPageWikicode = $this->p->makeTopicTalkPageWikicode($this->mainArticleTitle, $this->topicTitle, $nonMainArticleTitles, $this->goodOrFeatured, $this->datetime, $wikiProjectBanners, $this->nominationPageTitle);
		$this->wapi->edit($topicTalkPageTitle, $topicTalkPageWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step 4
	  */
	private function updateTalkPagesOfArticles() {
		$this->p->abortIfTooManyArticlesInTopic($this->allArticleTitles, $this->MAX_ARTICLES_ALLOWED_IN_TOPIC, $this->nominationPageTitle);
		foreach ( $this->allArticleTitles as $key => $articleTitle ) {
			$talkPageTitle = 'Talk:' . $articleTitle;

			$talkPageWikicode = $this->wapi->getpage($talkPageTitle);

			// $talkPageWikicode = addHeadingIfNeeded($talkPageWikicode, $talkPageTitle);

			$talkPageWikicode = $this->p->removeGTCFTCTemplate($talkPageWikicode);

			$talkPageWikicode = $this->p->addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);

			$nextActionNumber = $this->p->determineNextActionNumber(
				$talkPageWikicode,
				$this->ARTICLE_HISTORY_MAX_ACTIONS,
				$talkPageTitle
			);

			$oldid = $this->wapi->getRevisionIDOfMostRecentRevision($articleTitle);

			$talkPageWikicode = $this->p->updateArticleHistory(
				$talkPageWikicode,
				$nextActionNumber,
				$this->goodOrFeatured,
				$this->datetime,
				$this->mainArticleTitle,
				$this->topicTitle,
				$articleTitle,
				$talkPageTitle,
				$this->nominationPageTitle,
				$oldid
			);

			$this->wapi->edit(
				$talkPageTitle,
				$talkPageWikicode,
				$this->topicWikipediaPageTitle,
				$this->goodOrFeatured
			);
		}
	}

	/**
	  * Step 5
	  */
	private function updateCountPage() {
		$countPageTitle = ( $this->goodOrFeatured == 'good' ) ? 'Wikipedia:Good topics/count' : 'Wikipedia:Featured topics/count';
		$countPageWikicode = $this->wapi->getpage($countPageTitle);
		$articlesInTopic = count($this->allArticleTitles);
		$countPageWikicode = $this->p->updateCountPageTopicCount($countPageWikicode, $countPageTitle);
		$countPageWikicode = $this->p->updateCountPageArticleCount($countPageWikicode, $countPageTitle, $articlesInTopic);
		$this->wapi->edit($countPageTitle, $countPageWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step 5
	  */
	private function updateTemplateFeaturedTopicLog() {
		$countTemplateTitle = 'Template:Featured topic log';
		$countTemplateWikicode = $this->wapi->getpage($countTemplateTitle);
		$month = date('F');
		$year = date('Y');
		$countTemplateWikicode = $this->p->getTemplateFeaturedTopicLogWikicode($month, $year, $countTemplateWikicode, $this->goodOrFeatured);
		$this->wapi->edit($countTemplateTitle, $countTemplateWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step 7
	  */
	private function createChildCategories() {
		if ( $this->goodArticleCount > 0 ) {
			$goodArticleCategoryTitle = "Category:Wikipedia featured topics $this->topicTitle good content";
			$goodArticleCategoryWikitext = "[[Category:Wikipedia featured topics $this->topicTitle]]";
			$this->wapi->edit($goodArticleCategoryTitle, $goodArticleCategoryWikitext, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
		}
		if ( $this->featuredArticleCount > 0 ) {
			$featuredArticleCategoryTitle = "Category:Wikipedia featured topics $this->topicTitle featured content";
			$featuredArticleCategoryWikitext = "[[Category:Wikipedia featured topics $this->topicTitle]]";
			$this->wapi->edit($featuredArticleCategoryTitle, $featuredArticleCategoryWikitext, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
		}
	}

	/**
	  * Step 8
	  */
	private function createParentCategory() {
		$parentCategoryTitle = "Category:Wikipedia featured topics $this->topicTitle";
		$parentCategoryWikitext = "[[Category:Wikipedia featured topics categories|$this->topicTitle]]";
		$this->wapi->edit($parentCategoryTitle, $parentCategoryWikitext, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step 9
	  */
	private function addToFGTCLog() {
		$logPageTitle = $this->p->getLogPageTitle($this->datetime, $this->goodOrFeatured);
		$logPageWikicode = $this->wapi->getpage($logPageTitle);
		$logPageWikicode = trim($logPageWikicode . "\n{{" . $this->nominationPageTitle . '}}');
		$this->wapi->edit($logPageTitle, $logPageWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step 10
	  */
	private function addToTemplateAnnouncements() {
		$newFeaturedContentTitle = 'Template:Announcements/New featured content';
		$newFeaturedContentWikicode = $this->wapi->getpage($newFeaturedContentTitle);
		$newFeaturedContentWikicode = $this->p->addTopicToNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode, $this->topicWikipediaPageTitle, $this->topicTitle);
		$newFeaturedContentWikicode = $this->p->removeBottomTopicFromNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode);
		$this->wapi->edit($newFeaturedContentTitle, $newFeaturedContentWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step 10
	  */
	private function addToWikipediaGoingsOn() {
		$goingsOnTitle = 'Wikipedia:Goings-on';
		$goingsOnWikicode = $this->wapi->getpage($goingsOnTitle);
		$timestamp = time();
		$goingsOnWikicode = $this->p->addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $this->topicWikipediaPageTitle, $this->topicTitle, $timestamp);
		$this->wapi->edit($goingsOnTitle, $goingsOnWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step 11
	  */
	private function removeFromFGTC() {
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$fgtcWikicode = $this->wapi->getpage($fgtcTitle);
		$fgtcWikicode = $this->p->removeTopicFromFGTC($this->nominationPageTitle, $fgtcWikicode, $fgtcTitle);
		$this->wapi->edit($fgtcTitle, $fgtcWikicode, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
	}

	/**
	  * Step 6
	  */
	private function printConsoleReminderAboutStep6() {
		$pageToAddTo = ($this->goodOrFeatured == 'good') ? '[[Wikipedia:Good topics]]' : '[[Wikipedia:Featured topics]]';

		$this->eh->echoAndFlush("Step 6 must be done manually. Add {{{$this->topicWikipediaPageTitle}}} to the appropriate section of $pageToAddTo", 'message');
	}

	/**
	  * Step 1
	  */
	private function writeMessageOnArchivePage() {
		// Replace template invokation with Success. ~~~~ or Error. ~~~~
		// Also change {{User:NovemBot/Promote}} to include |done=yes, which will prevent the bot from going into an endless loop every hour.
		$this->nominationPageWikicode = $this->wapi->getpage($this->nominationPageTitle); // Fetch a fresh copy of the nomination page, to prevent edit conflicts.
		$this->nominationPageWikicode = $this->p->markDoneAndSuccessful($this->nominationPageWikicode, $this->nominationPageTitle, $this->topicWikipediaPageTitle, $this->goodOrFeatured);
		$this->wapi->edit(
			$this->nominationPageTitle,
			$this->nominationPageWikicode,
			$this->topicWikipediaPageTitle,
			$this->goodOrFeatured
		);
	}

	private function handleError($e) {
		$errorMessage = $e->getMessage();

		$this->eh->logError($errorMessage);

		// write error to /archive1 page, and ping maintainer
		$this->nominationPageWikicode = $this->wapi->getpage($this->nominationPageTitle);
		$this->nominationPageWikicode = $this->p->markError($this->nominationPageWikicode, $this->nominationPageTitle, $errorMessage);
		$editSummary = 'Log issue that prevented this topic from being promoted by the promotion bot. Ping [[User:Novem Linguae]]. (NovemBot Task 1)';
		$this->wapi->editSimple($this->nominationPageTitle, $this->nominationPageWikicode, $editSummary);

		// write error to log page
		$this->logPageWikicode = $this->wapi->getpage($this->logPageTitle);
		$this->logPageWikicode .= "\n* [[$this->nominationPageTitle]] - ~~~~~ - <span style=\"color: red; font-weight: bold;\">ERROR:</span> $errorMessage";
		$this->wapi->edit(
			$this->logPageTitle,
			$this->logPageWikicode,
			// typecast these from null to '', in case these aren't set at the time of the error
			(string) $this->topicWikipediaPageTitle,
			(string) $this->goodOrFeatured
		);
	}
}
