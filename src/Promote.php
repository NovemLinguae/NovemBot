<?php

class Promote {
	function __construct(EchoHelper $eh, Helper $h) {
		$this->eh = $eh;
		$this->h = $h;
	}
	
	// TODO: is this dead code?
	function sliceNovemBotPromoteTemplate($wikicode, $title) {
		preg_match('/\{\{User:NovemBot\/Promote([^\}]*)\}\}/i', $wikicode, $matches);
		if ( ! $matches ) {
			throw new GiveUpOnThisTopic("On page $title, unable to find {{User:NovemBot/Promote}} template.");
		}
		$templateWikicode = $matches[1];
		return $templateWikicode;
	}

	function abortIfAddToTopic($callerPageWikicode, $title) {
		preg_match('/\{\{Add to topic/i', $callerPageWikicode, $matches);
		if ( $matches ) {
			throw new GiveUpOnThisTopic("On page $title, {{Add to topic}} is present. Bot does not know how to handle these.");
		}
	}
	
	function getTopicBoxWikicode($callerPageWikicode, $title) {
		$wikicode = $this->h->sliceFirstTemplateFound($callerPageWikicode, 'good topic box');
		if ( $wikicode ) {
			return $wikicode;
		}
		$wikicode = $this->h->sliceFirstTemplateFound($callerPageWikicode, 'featured topic box');
		if ( $wikicode ) {
			return $wikicode;
		}
		throw new GiveUpOnThisTopic("On page $title, {{Good/featured topic box}} not found.");
	}

	/** This is differen than getTopicTitle(). This is needed to figure out the main article's title. */
	function getMainArticleTitle($topicBoxWikicode, $title) {
		// TODO: handle piped links
		preg_match("/\|\s*lead\s*=\s*{{\s*(?:class)?icon\s*\|\s*(?:FA|GA|FL)\s*}}\s*(?:'')?\[\[([^\]\|]*)/i", $topicBoxWikicode, $matches);
		if ( ! $matches ) {
			throw new GiveUpOnThisTopic("On page $title, could not find main article name in {{Good/Featured topic box}}.");
		}
		$mainArticleTitle = $matches[1];
		return $mainArticleTitle;
	}

	/** There's 3 sources we can pick the topic name from: 1) main article's title, 2) |title= parameter, 3) /archive page's title. Per a conversation with Aza24, we will get it from #2: the |title= parameter. */
	function getTopicTitle($topicBoxWikicode, $mainArticleTitle) {
		// search for |title= parameter
		preg_match("/\|\s*title\s*=\s*([^\|\}]+)\s*/is", $topicBoxWikicode, $matches);
		
		// if not found, return $mainArticleTitle as topicTitle
		if ( ! $matches ) {
			return $mainArticleTitle;
		}
		
		// if found, return that as topicTitle
		return trim($matches[1]);
	}

	/** It's OK if this one isn't able to find anything. Not a critical error. It can return blank. */
	function getTopicDescriptionWikicode($callerPageWikicode) {
		preg_match('/===(\n.*?)\{\{(?:Featured topic box|Good topic box)/si', $callerPageWikicode, $matches);
		$output = $matches ? $matches[1] : '';
		if ( $output ) {
			$output = str_replace('<!---<noinclude>--->', '', $output);
			$output = str_replace('<!---</noinclude>--->', '', $output);
			$output = str_replace('<noinclude>', '', $output);
			$output = str_replace('</noinclude>', '', $output);
			$output = trim($output);
			$output = '<noinclude>' . $output . '</noinclude>';
		}
		return $output;
	}

	function getTopicWikipediaPageTitle($topicTitle) {
		return "Wikipedia:Featured topics/$topicTitle";
	}

	function getTopicWikipediaPageWikicode($topicDescriptionWikicode, $topicBoxWikicode) {
		// Put only one line break. More than one line break causes excess whitespace when the page is transcluded into other pages in step 6.
		$output = trim($topicDescriptionWikicode . "\n" . $topicBoxWikicode);
		return $output;
	}

	function getDatetime() {
		$date = date('H:m, j F Y');
		return $date;
	}

	function getAllArticleTitles($topicBoxWikicode, $title) {
		// Confirmed that it's just FA, GA, FL. There won't be any other icons.
		preg_match_all("/{{\s*(?:class)?icon\s*\|\s*(?:FA|GA|FL)\}\}\s*(.*)\s*$/im", $topicBoxWikicode, $matches);
		if ( ! $matches[1] ) {
			throw new GiveUpOnThisTopic("On page $title, could not find list of topics inside of {{Featured topic box}}.");
		}
		$listOfTitles = $matches[1];
		$this->eh->html_var_export($listOfTitles, 'variable');
		
		// parse each potential title
		foreach ( $listOfTitles as $key => $title2 ) {
			// throw an error if any of the article names are templates, or not article links
			if ( strpos($title2, '{') !== false ) {
				throw new GiveUpOnThisTopic("On page $title, when parsing the list of topics in {{featured topic box}}, found some templates. Try subst:-ing them, then re-running the bot.");
			}
			
			// get rid of wikilink syntax around it
			$match = $this->h->preg_first_match('/\[\[([^\|\]]*)(?:\|[^\|\]]*)?\]\]/is', $title2);
			if ( ! $match ) {
				throw new GiveUpOnThisTopic("On page $title, when parsing the list of topics in {{featured topic box}}, found an improperly formatted title. No wikilink found.");
			}
			$listOfTitles[$key] = $match;
			
			// convert &#32; to space. fixes an issue with subst-ing ship templates such as {{ship}} and {{sclass}}
			$listOfTitles[$key] = preg_replace('/&#32;/', ' ', $listOfTitles[$key]);
		}
		
		$this->eh->html_var_export($listOfTitles, 'variable');
		return $listOfTitles;
	}

	function makeTopicTalkPageWikicode($mainArticleTitle, $topicTitle, $nonMainArticleTitles, $goodOrFeatured, $datetime, $wikiProjectBanners, $nominationPageTitle) {
		assert($goodOrFeatured == 'good' || $goodOrFeatured == 'featured');
		$nonMainArticleTitlestring = '';
		$count = 1;
		$lastArticleNumber = count($nonMainArticleTitles);
		foreach ( $nonMainArticleTitles as $key => $value ) {
			$and = '';
			if ( $count == $lastArticleNumber ) {
				$and = ' and';
			}
			$nonMainArticleTitlestring .= ",$and [[$value]]";
			$count++;
		}
		$actionCode = ($goodOrFeatured == 'good') ? 'GTC' : 'FTC';
		$talkWikicode = 
"{{Featuredtopictalk
|title = $topicTitle
|action1 = $actionCode
|action1date = $datetime
|action1link = $nominationPageTitle
|action1result = '''Promoted''' with articles '''[[$mainArticleTitle]]'''$nonMainArticleTitlestring
|currentstatus = current
}}
$wikiProjectBanners";
		return $talkWikicode;
	}

	function getTopicTalkPageTitle($topicTitle) {
		return 'Wikipedia talk:Featured topics/' . $topicTitle;
	}

	function getWikiProjectBanners($mainArticleTalkPageWikicode, $title) {
		// Match WikiProject banners
		// Do not match template parameters such as |class=GA|importance=Low
		// We will have to add }} to the end of the matches later
		preg_match_all('/\{\{(WikiProject (?!banner|shell)[^\|\}]*)/i', $mainArticleTalkPageWikicode, $matches);
		if ( ! $matches ) {
			throw new GiveUpOnThisTopic("On page $title, could not find WikiProject banners on main article's talk page.");
		}
		$bannerWikicode = '';
		foreach ( $matches[0] as $key => $value ) {
			$bannerWikicode .= trim($value) . "}}\n";
		}
		$bannerWikicode = substr($bannerWikicode, 0, -1); // chop off last \n
		if ( count($matches[0]) > 1 ) {
			$bannerWikicode = "{{WikiProject banner shell|1=\n".$bannerWikicode."\n}}";
		}
		return $bannerWikicode;
	}

	function getNonMainArticleTitles($allArticleTitles, $mainArticleTitle) {
		return $this->h->deleteArrayValue($allArticleTitles, $mainArticleTitle);
	}
	
	function abortIfTooManyArticlesInTopic($allArticleTitles, $MAX_ARTICLES_ALLOWED_IN_TOPIC, $title) {
		if ( count($allArticleTitles) > $MAX_ARTICLES_ALLOWED_IN_TOPIC ) {
			throw new GiveUpOnThisTopic("On page $title, too many topics in the topic box.");
		}
	}

	function removeGTCFTCTemplate($talkPageWikicode) {
		return preg_replace('/\{\{(?:gtc|ftc)[^\}]*\}\}\n/i', '', $talkPageWikicode);
	}

	/** Determine next |action= number in {{Article history}} template. This is so we can insert an action. */
	function determineNextActionNumber($talkPageWikicode, $ARTICLE_HISTORY_MAX_ACTIONS, $talkPageTitle) {
		for ( $i = $ARTICLE_HISTORY_MAX_ACTIONS; $i >= 1; $i-- ) {
			$hasAction = preg_match("/\|\s*action$i\s*=/i", $talkPageWikicode);
			if ( $hasAction ) {
				return $i + 1;
			}
		}
		throw new GiveUpOnThisTopic("On page $talkPageTitle, in {{Article history}} template, unable to determine next |action= number.");
	}

	function updateArticleHistory($talkPageWikicode, $nextActionNumber, $goodOrFeatured, $datetime, $mainArticleTitle, $topicTitle, $articleTitle, $talkPageTitle, $nominationPageTitle) {
		assert($goodOrFeatured == 'good' || $goodOrFeatured == 'featured');
		$main = ( $mainArticleTitle == $articleTitle ) ? 'yes' : 'no';
		$ftcOrGTC = ( $goodOrFeatured == 'featured' ) ? 'FTC' : 'GTC';
		$addToArticleHistory = 
"|action$nextActionNumber = $ftcOrGTC
|action{$nextActionNumber}date = $datetime
|action{$nextActionNumber}link = $nominationPageTitle
|action{$nextActionNumber}result = promoted
|ftname = $topicTitle
|ftmain = $main";
		$newWikicode = $this->h->insertCodeAtEndOfFirstTemplate($talkPageWikicode, 'Article ?history', $addToArticleHistory);
		if ( $newWikicode == $talkPageWikicode ) {
			throw new GiveUpOnThisTopic("On page $talkPageTitle, in {{Article history}} template, unable to determine where to add new actions.");
		}
		return $newWikicode;
	}

	/** There's a {{GA}} template that some people use instead of {{Article history}}. If this is present, replace it with {{Article history}}. */
	function addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle) {
		$hasArticleHistory = preg_match('/\{\{Article ? history([^\}]*)\}\}/i', $talkPageWikicode);
		$gaTemplateWikicode = $this->h->preg_first_match('/(\{\{GA[^\}]*\}\})/i', $talkPageWikicode);
		if ( ! $hasArticleHistory && $gaTemplateWikicode ) {
			// delete {{ga}} template
			$talkPageWikicode = preg_replace('/\{\{GA[^\}]*\}\}\n?/i', '', $talkPageWikicode);
			$talkPageWikicode = trim($talkPageWikicode);
			
			// parse its parameters
			// example: |21:00, 12 March 2017 (UTC)|topic=Sports and recreation|page=1|oldid=769997774
			$parameters = $this->getParametersFromTemplateWikicode($gaTemplateWikicode);
			
			// if no page specified, assume page is 1. so then the good article review link will be parsed as /GA1
			if ( ! isset($parameters['page']) || ! $parameters['page'] ) {
				$parameters['page'] = 1;
			}
			
			$topicString = '';
			if ( isset($parameters['topic']) ) {
				$topicString = "\n|topic = {$parameters['topic']}";
			} elseif ( isset($parameters['subtopic']) ) { // subtopic is an alias only used in {{ga}}, it is not used in {{article history}}
				$topicString = "\n|topic = {$parameters['subtopic']}";
			}
			
			$date = date('Y-m-d', strtotime($parameters[1]));
			
			// insert {{article history}} template
			$addToTalkPageAboveWikiProjects = 
"{{Article history
|currentstatus = GA"
. $topicString . "

|action1 = GAN
|action1date = $date
|action1link = $talkPageTitle/GA{$parameters['page']}
|action1result = listed
|action1oldid = {$parameters['oldid']}
}}";
			$talkPageWikicode = $this->addToTalkPageAboveWikiProjects($talkPageWikicode, $addToTalkPageAboveWikiProjects);
		}
		return $talkPageWikicode;
	}

	/** Add wikicode right above {{WikiProject X}} or {{WikiProject Banner Shell}} if present, or first ==Header== if present, or at bottom of page. Treat {{Talk:abc/GA1}} as a header. */
	function addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd) {
		if ( ! $talkPageWikicode ) {
			return $wikicodeToAdd;
		}
		
		// Find first WikiProject or WikiProject banner shell template
		$wikiProjectLocation = false;
		$dictionary = ['wikiproject', 'wpb', 'wpbs', 'wpbannershell', 'wp banner shell', 'bannershell', 'scope shell', 'project shell', 'multiple wikiprojects', 'football'];
		foreach ( $dictionary as $key => $value ) {
			$location = stripos($talkPageWikicode, '{{' . $value); // case insensitive
			if ( $location !== false ) {
				// if this location is higher up than the previous found location, overwrite it
				if ( $wikiProjectLocation === false || $wikiProjectLocation > $location ) {
					$wikiProjectLocation = $location;
				}
			}
		}
		
		// Find first heading
		$headingLocation = strpos($talkPageWikicode, '==');
		
		// Find first {{Talk:abc/GA1}} template
		$gaTemplateLocation = $this->h->preg_position('/{{[^\}]*\/GA\d{1,2}}}/is', $talkPageWikicode);
		
		// Set insert location
		if ( $wikiProjectLocation !== false ) {
			$insertPosition = $wikiProjectLocation;
		} elseif ( $headingLocation !== false ) {
			$insertPosition = $headingLocation;
		} elseif ( $gaTemplateLocation !== false ) {
			$insertPosition = $gaTemplateLocation;
		} else {
			$insertPosition = strlen($talkPageWikicode); // insert at end of page
		}
		
		// if there's a {{Talk:abc/GA1}} above a heading, adjust for this
		if (
			$headingLocation !== false &&
			$gaTemplateLocation !== false &&
			$gaTemplateLocation < $insertPosition
		) {
			$insertPosition = $gaTemplateLocation;
		}
		
		// If there's excess newlines in front of the insert location, delete the newlines
		$deleteTopPosition = false;
		$deleteBottomPosition = false;
		$pos = $insertPosition - 1;
		$i = 1;
		while ( $pos != 0 ) {
			$char = substr($talkPageWikicode, $pos, 1);
			if ( $char == "\n" ) {
				if ( $i != 1 && $i != 2 ) { // skip first two \n, those are OK to keep
					$deleteTopPosition = $pos;
					if ( $i == 3 ) {
						$deleteBottomPosition = $insertPosition;
					}
				}
				$insertPosition = $pos; // insert position should back up past all \n's.
				$i++;
				$pos--;
			} else {
				break;
			}
		}
		if ( $deleteTopPosition !== false ) {
			$talkPageWikicode = $this->h->deleteMiddleOfString($talkPageWikicode, $deleteTopPosition, $deleteBottomPosition);
		}
		
		$lengthOfRightHalf = strlen($talkPageWikicode) - $insertPosition;
		$leftHalf = substr($talkPageWikicode, 0, $insertPosition);
		$rightHalf = substr($talkPageWikicode, $insertPosition, $lengthOfRightHalf);
		
		if ( $insertPosition == 0 ) {
			return $wikicodeToAdd . "\n" . $talkPageWikicode;
		} else {
			return $leftHalf . "\n" . $wikicodeToAdd . $rightHalf;
		}
	}

	function getParametersFromTemplateWikicode($wikicode) {
		$wikicode = substr($wikicode, 2, -2); // remove {{ and }}
		// TODO: explode without exploding | inside of inner templates
		$strings = explode('|', $wikicode);
		$parameters = [];
		$unnamedParameterCount = 1;
		$i = 0;
		foreach ( $strings as $key => $string ) {
			$i++;
			if ( $i == 1 ) continue; // skip the template name, this is not a parameter 
			$hasEquals = strpos($string, '=');
			if ( $hasEquals === false ) {
				$parameters[$unnamedParameterCount] = $string;
				$unnamedParameterCount++;
			} else {
				preg_match('/^([^=]*)=(.*)$/s', $string, $matches); // isolate param name and param value by looking for first equals sign
				$paramName = strtolower(trim($matches[1]));
				$paramValue = trim($matches[2]);
				$parameters[$paramName] = $paramValue;
			}
		}
		return $parameters;
	}

	function updateCountPageTopicCount($countPageWikicode, $countPageTitle) {
		$count = $this->h->preg_first_match("/currently '''([,\d]+)'''/", $countPageWikicode);
		$count = str_replace(',', '', $count); // remove commas
		if ( ! $count ) {
			throw new GiveUpOnThisTopic("On page $countPageTitle, unable to find the total topic count.");
		}
		$count++;
		$count = number_format($count); // add commas back
		$countPageWikicode = preg_replace("/(currently ''')([,\d]+)(''')/", '${1}'.$count.'${3}', $countPageWikicode);
		return $countPageWikicode;
	}

	function updateCountPageArticleCount($countPageWikicode, $countPageTitle, $articlesInTopic) {
		$count = $this->h->preg_first_match("/encompass '''([,\d]+)'''/", $countPageWikicode);
		$count = str_replace(',', '', $count); // remove commas
		if ( ! $count ) {
			throw new GiveUpOnThisTopic("On page $countPageTitle, unable to find the total article count.");
		}
		$count += $articlesInTopic;
		$count = number_format($count); // add commas back
		$countPageWikicode = preg_replace("/(encompass ''')([,\d]+)(''')/", '${1}'.$count.'${3}', $countPageWikicode);
		return $countPageWikicode;
	}

	function getLogPageTitle($datetime, $goodOrFeatured) {
		$goodOrFeatured = ucfirst($goodOrFeatured);
		$monthAndYear = date('F Y', strtotime($datetime));
		return "Wikipedia:Featured and good topic candidates/$goodOrFeatured log/$monthAndYear";
	}

	function addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $topicTitle, $timestamp) {
		$date = date('j M', $timestamp); // gmdate = UTC
		$newWikicode = preg_replace("/('''\[\[Wikipedia:Featured topics\|Topics]] that gained featured status'''.*?)(\|})/s", "$1* [[$topicWikipediaPageTitle|$topicTitle]] ($date)\n$2", $goingsOnWikicode);
		if ( $newWikicode == $goingsOnWikicode ) {
			throw new GiveUpOnThisTopic("On page $goingsOnTitle, unable to figure out where to insert code.");
		}
		return $newWikicode;
	}

	function addTopicToNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $topicTitle) {
		$newWikicode = preg_replace("/(<!-- Topics \(15, most recent first\) -->)/", "$1\n* [[$topicWikipediaPageTitle|$topicTitle]]", $newFeaturedContentWikicode);
		if ( $newWikicode == $newFeaturedContentWikicode ) {
			throw new GiveUpOnThisTopic("On page $newFeaturedContentTitle, unable to figure out where to insert code.");
		}
		return $newWikicode;
	}

	function removeBottomTopicFromNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode) {
		$wikicode15MostRecentTopics = $this->h->preg_first_match("/<!-- Topics \(15, most recent first\) -->\n(.*?)<\/div>/s", $newFeaturedContentWikicode);
		$wikicode15MostRecentTopics = trim($wikicode15MostRecentTopics);
		if ( ! $wikicode15MostRecentTopics ) {
			throw new GiveUpOnThisTopic("On page $newFeaturedContentTitle, unable to find wikicode for 15 most recent topics.");
		}
		$wikicode15MostRecentTopics = $this->h->deleteLastLineOfString($wikicode15MostRecentTopics);
		$newWikicode = preg_replace("/(<!-- Topics \(15, most recent first\) -->\n)(.*?)(<\/div>)/s", "$1$wikicode15MostRecentTopics\n\n$3", $newFeaturedContentWikicode);
		if ( $newWikicode == $newFeaturedContentWikicode ) {
			throw new GiveUpOnThisTopic("On page $newFeaturedContentTitle, unable to delete oldest topic.");
		}
		return $newWikicode;
	}
	
	function getGoodArticleCount($topicBoxWikicode) {
		preg_match_all('/{{\s*(?:class)?icon\s*\|\s*(?:GA)\s*}}/i', $topicBoxWikicode, $matches);
		$count = count($matches[0]);
		$this->eh->echoAndFlush($count, 'variable');
		return $count;
	}
	
	function getFeaturedArticleCount($topicBoxWikicode) {
		preg_match_all('/{{\s*(?:class)?icon\s*\|\s*(?:FA|FL)\s*}}/i', $topicBoxWikicode, $matches);
		$count = count($matches[0]);
		$this->eh->echoAndFlush($count, 'variable');
		return $count;
	}
	
	function markDoneAndSuccessful($nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured) {
		$nominationPageWikicode2 = preg_replace('/({{\s*User:NovemBot\/Promote\s*)(}}.*?\(UTC\))/is', "$1|done=yes$2", $nominationPageWikicode);
		if ( $nominationPageWikicode == $nominationPageWikicode2 ) {
			throw new GiveUpOnThisTopic("On page $nominationPageTitle, unable to find {{User:NovemBot/Promote}} template and signature.");
		}
		
		$pageToAddTo = ($goodOrFeatured == 'good') ? '[[Wikipedia:Good topics]]' : '[[Wikipedia:Featured topics]]';
		
		$nominationPageWikicode2 = trim($nominationPageWikicode2) . "\n* {{Done}}. Promotion completed successfully. Don't forget to add <nowiki>{{{$topicWikipediaPageTitle}}}</nowiki> to the appropriate section of $pageToAddTo. ~~~~";
		
		return $nominationPageWikicode2;
	}
	
	function markError($nominationPageWikicode, $nominationPageTitle, $errorMessage) {
		// toggle the original summoning code to done=yes, so that this page doesn't get processed over and over again, and so this error doesn't get written over and over again
		$nominationPageWikicode = preg_replace('/({{\s*User:NovemBot\/Promote\s*)(}}.*?\(UTC\))/is', "$1|done=yes$2", $nominationPageWikicode);
		
		// TODO: also look for and remove [[Category: blah blah]]
		// TODO: if neither of those changes resulted in a change to the Wikitext, throw a fatal error, to prevent a loop where the bot writes an error message every hour
		
		// add an error message to the summoning page
		$nominationPageWikicode .= "\n* {{N.b.}} There was an issue that prevented the promotion bot from promoting this topic. Please solve the issue and run the bot again. The error description is: <code><nowiki>$errorMessage</nowiki></code> ~~~~";
		
		return $nominationPageWikicode;
	}

	/** In the {{Featured topic box}} template, makes sure that it has the parameter view=yes. For example, {{Featured topic box|view=yes}} */
	function setTopicBoxViewParameterToYes($topicBoxWikicode) {
		$hasViewYes = preg_match('/\|\s*view\s*=\s*yes\s*[\|\}]/si', $topicBoxWikicode);
		if ( $hasViewYes ) return $topicBoxWikicode;
		// delete view = anything
		$topicBoxWikicode = preg_replace('/\|\s*view\s*=[^\|\}]*([\|\}])/si', '$1', $topicBoxWikicode);
		// if the template ended up as {{Template\n}}, get rid of the \n
		$topicBoxWikicode = preg_replace('/({{.*)\n{1,}(}})/i', '$1$2', $topicBoxWikicode);
		// add view = yes
		$topicBoxWikicode = $this->h->insertCodeAtEndOfFirstTemplate($topicBoxWikicode, 'Featured topic box', '|view=yes');
		return $topicBoxWikicode;
	}

	/** In the {{Featured topic box}} template, makes sure it has a |title=. Else the discuss link will be red. */
	function setTopicBoxTitleParameter($topicBoxWikicode, $mainArticleTitle) {
		$hasBlankTitleParameter = preg_match('/(\|\s*title\s*=)(\s*)([\|\}])/is', $topicBoxWikicode);
		$hasTitleParameter = preg_match('/\|\s*title\s*=/is', $topicBoxWikicode);
		if ( $hasBlankTitleParameter ) {
			return preg_replace('/(\|\s*title\s*=)(\s*)([\|\}])/is', "$1$mainArticleTitle$3", $topicBoxWikicode);
		} elseif (! $hasTitleParameter) { // if |title is not found, append it to the end of the template
			return $this->h->insertCodeAtEndOfFirstTemplate($topicBoxWikicode, 'featured topic box', "|title=$mainArticleTitle");
		}
		// else title is already present, do nothing
		return $topicBoxWikicode;
	}

	/** In the {{Featured topic box}} template, makes sure that if the title parameter has something like |title=''Meet the Who 2'', that the '' is removed so that the "discuss" link isn't broken. */
	function cleanTopicBoxTitleParameter($topicBoxWikicode) {
		return preg_replace("/(\|\s*title\s*=\s*)''([^\|\}]*)''(\s*[\|\}])/is", '$1$2$3', $topicBoxWikicode);
	}

	/** Topic descriptions should not have user signatures. Strip these out. */
	function removeSignaturesFromTopicDescription($topicDescriptionWikicode) {
		return preg_replace("/ \[\[User:.*\(UTC\)/is", '', $topicDescriptionWikicode);
	}

	/** Takes the wikicode of the page [[Wikipedia:Featured and good topic candidates]], and removes the nomination page from it. For example, if the nomination page title is "Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1", it will remove {{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}} from the page. */
	function removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle) {
		$wikicode2 = str_replace("{{" . $nominationPageTitle . "}}\n", '', $fgtcWikicode);
		$wikicode2 = str_replace("\n{{" . $nominationPageTitle . "}}", '', $wikicode2);
		/* OK if this is missing. No need to throw a fatal error
		if ( $fgtcWikicode == $wikicode2 ) {
			throw new GiveUpOnThisTopic("On page $fgtcTitle, unable to locate {{" . $nominationPageTitle . "}}.");
		}
		*/
		return $wikicode2;
	}
	
	function checkCounts($goodArticleCount, $featuredArticleCount, $allArticleTitles) {
		if ( $goodArticleCount + $featuredArticleCount <= 0 ) {
			throw new GiveUpOnThisTopic("Unexpected value for the count of good articles and featured articles in the topic. Sum is 0 or less.");
		}
		if ( $goodArticleCount + $featuredArticleCount != count($allArticleTitles) ) {
			throw new GiveUpOnThisTopic("Unexpected value for the count of good articles and featured articles in the topic. Sum is not equal to the number of articles detected in {{Featured topic box}}.");
		}
		// Good/featured topics should have at least 2 articles. If not, something is wrong.
		if ( count($allArticleTitles) < 2 ) {
			throw new GiveUpOnThisTopic("When parsing the list of topics in {{featured topic box}}, found less than 2 articles.");
		}
	}
	
	// Per https://en.wikipedia.org/wiki/Wikipedia_talk:Featured_and_good_topic_candidates, 50% = featured topic, not good topic.
	function decideIfGoodOrFeatured($goodArticleCount, $featuredArticleCount) {
		if ( $featuredArticleCount >= $goodArticleCount ) {
			return 'featured';
		}
		return 'good';
	}
}