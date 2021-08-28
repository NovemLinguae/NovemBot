<?php

function sliceNovemBotPromoteTemplate($wikicode, $title) {
	preg_match('/\{\{User:NovemBot\/Promote([^\}]*)\}\}/i', $wikicode, $matches);
	if ( ! $matches ) {
		throw new giveUpOnThisTopic("On page $title, unable to find {{User:NovemBot/Promote}} template.");
	}
	$templateWikicode = $matches[1];
	//echoAndFlush($templateWikicode, 'variable');
	return $templateWikicode;
}

function abortIfAddToTopic($callerPageWikicode, $title) {
	preg_match('/\{\{Add to topic/i', $callerPageWikicode, $matches);
	if ( $matches ) {
		throw new giveUpOnThisTopic("On page $title, {{Add to topic}} is present. Bot does not know how to handle these.");
	}
}

function getGoodOrFeaturedFromNovemBotTemplate($novemBotTemplateWikicode, $title) {
	preg_match('/\|type=([^\|\}]*)/', $novemBotTemplateWikicode, $matches);
	if ( ! $matches ) {
		throw new giveUpOnThisTopic("On page $title, unable to find |type= parameter of {{User:NovemBot/Promote}}.");
	}
	$type = $matches[1];
	if ( $type != 'good' && $type != 'featured' ) {
		throw new giveUpOnThisTopic("On page $title, |type= parameter of {{User:NovemBot/Promote}} must be \"good\" or \"featured\|.");
	}
	//echoAndFlush($type, 'variable');
	return $type;
}

function getTopicBoxWikicode($callerPageWikicode, $title) {
	$wikicode = sliceFirstTemplateFound($callerPageWikicode, 'good topic box');
	if ( $wikicode ) {
		return $wikicode;
	}
	$wikicode = sliceFirstTemplateFound($callerPageWikicode, 'featured topic box');
	if ( $wikicode ) {
		return $wikicode;
	}
	throw new giveUpOnThisTopic("On page $title, {{Good/featured topic box}} not found.");
}

function getMainArticleTitle($topicBoxWikicode, $title) {
	// TODO: are there any other possible icons besides FA, GA, FL?
	// TODO: handle piped links
	preg_match("/\|\s*lead\s*=\s*{{\s*(?:class)?icon\s*\|\s*(?:FA|GA|FL)\s*}}\s*(?:'')?\[\[([^\]\|]*)/i", $topicBoxWikicode, $matches);
	if ( ! $matches ) {
		throw new giveUpOnThisTopic("On page $title, could not find main article name in {{Good/Featured topic box}}.");
	}
	$mainArticleTitle = $matches[1];
	//echoAndFlush($mainArticleTitle, 'variable');
	return $mainArticleTitle;
}

/** It's OK if this one isn't able to find anything. Not a critical error. It can return blank. */
function getTopicDescriptionWikicode($callerPageWikicode) {
	preg_match('/===(\n.*?)\{\{/s', $callerPageWikicode, $matches);
	$output = $matches ? $matches[1] : '';
	if ( $output ) {
		$output = str_replace('<!---<noinclude>--->', '', $output);
		$output = str_replace('<!---</noinclude>--->', '', $output);
		$output = str_replace('<noinclude>', '', $output);
		$output = str_replace('</noinclude>', '', $output);
		$output = trim($output);
		$output = '<noinclude>' . $output . '</noinclude>';
	}
	//echoAndFlush($output, 'variable');
	return $output;
}

function getTopicWikipediaPageTitle($mainArticleTitle, $goodOrFeatured) {
	// assert($goodOrFeatured == 'good' || $goodOrFeatured == 'featured');
	// return 'Wikipedia:' . ucfirst($goodOrFeatured) . ' topics/' . $mainArticleTitle;
	return "Wikipedia:Featured topics/$mainArticleTitle";
}

function getTopicWikipediaPageWikicode($topicDescriptionWikicode, $topicBoxWikicode) {
	// Put only one line break. More than one line break causes excess whitespace when the page is transcluded into other pages in step 6.
	$output = trim($topicDescriptionWikicode . "\n" . $topicBoxWikicode);
	return $output;
}

function getDatetime() {
	date_default_timezone_set('UTC');
	$date = date('H:m, j F Y');
	// echoAndFlush($date, 'variable');
	return $date;
}

function getAllArticleTitles($topicBoxWikicode, $title) {
	// Confirmed that it's just FA, GA, FL. There won't be any other icons.
	preg_match_all("/{{\s*(?:class)?icon\s*\|\s*(?:FA|GA|FL)\s*}}\s*(?:''|\")?\[\[([^\|\]]*)/i", $topicBoxWikicode, $matches);
	if ( ! $matches[1] ) {
		throw new giveUpOnThisTopic("On page $title, could not find list of topics inside of {{Featured topic box}}.");
	}
	html_var_export($matches[1], 'variable');
	return $matches[1];
}

function getTopicTalkPageWikicode($mainArticleTitle, $nonMainArticleTitles, $goodOrFeatured, $datetime, $wikiProjectBanners, $nominationPageTitle) {
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
|title = $mainArticleTitle
|action1 = $actionCode
|action1date = $datetime
|action1link = $nominationPageTitle
|action1result = '''Promoted''' with articles '''[[$mainArticleTitle]]'''$nonMainArticleTitlestring
|currentstatus = current
}}
$wikiProjectBanners";
	return $talkWikicode;
}

function getTopicTalkPageTitle($mainArticleTitle, $goodOrFeatured) {
	assert($goodOrFeatured == 'good' || $goodOrFeatured == 'featured');
	return 'Wikipedia talk:' . ucfirst($goodOrFeatured) . ' topics/' . $mainArticleTitle;
}

function getWikiProjectBanners($mainArticleTalkPageWikicode, $title) {
	preg_match_all('/\{\{WikiProject (?!banner)[^\}]*\}\}/i', $mainArticleTalkPageWikicode, $matches);
	if ( ! $matches ) {
		throw new giveUpOnThisTopic("On page $title, could not find WikiProject banners on main article's talk page.");
	}
	$bannerWikicode = '';
	//html_var_export($matches, 'variable');
	foreach ( $matches[0] as $key => $value ) {
		$bannerWikicode .= $value . "\n";
	}
	$bannerWikicode = substr($bannerWikicode, 0, -1); // chop off last \n
	if ( count($matches[0]) > 1 ) {
		$bannerWikicode = "{{WikiProject banner shell|1=\n".$bannerWikicode."\n}}";
	}
	//echoAndFlush($bannerWikicode, 'variable');
	return $bannerWikicode;
}

function getNonMainArticleTitles($allArticleTitles, $mainArticleTitle) {
	return deleteArrayValue($mainArticleTitle, $allArticleTitles);
}

function deleteArrayValue(string $needle, array $haystack) {
	return array_diff($haystack, [$needle]);
}

function abortIfTooManyArticlesInTopic($allArticleTitles, $MAX_ARTICLES_ALLOWED_IN_TOPIC, $title) {
	if ( count($allArticleTitles) > $MAX_ARTICLES_ALLOWED_IN_TOPIC ) {
		throw new giveUpOnThisTopic("On page $title, too many topics in the topic box.");
	}
}

function removeGTCFTCTemplate($talkPageWikicode) {
	return preg_replace('/\{\{(?:gtc|ftc)[^\}]*\}\}\n/i', '', $talkPageWikicode);
}

function determineNextActionNumber($talkPageWikicode, $ARTICLE_HISTORY_MAX_ACTIONS, $talkPageTitle) {
	for ( $i = $ARTICLE_HISTORY_MAX_ACTIONS; $i >= 1; $i-- ) {
		$hasAction = preg_match("/\|\s*action$i\s*=/i", $talkPageWikicode);
		if ( $hasAction ) {
			//echoAndFlush($i + 1, 'variable');
			return $i + 1;
		}
	}
	throw new giveUpOnThisTopic("On page $talkPageTitle, in {{Article history}} template, unable to determine next |action= number.");
}

function updateArticleHistory($talkPageWikicode, $nextActionNumber, $goodOrFeatured, $datetime, $mainArticleTitle, $articleTitle, $talkPageTitle, $nominationPageTitle) {
	assert($goodOrFeatured == 'good' || $goodOrFeatured == 'featured');
	$main = ( $mainArticleTitle == $articleTitle ) ? 'yes' : 'no';
	$ftcOrGTC = ( $goodOrFeatured == 'featured' ) ? 'FTC' : 'GTC';
	$addToArticleHistory = 
"|action$nextActionNumber = $ftcOrGTC
|action{$nextActionNumber}date = $datetime
|action{$nextActionNumber}link = $nominationPageTitle
|action{$nextActionNumber}result = promoted
|ftname = $mainArticleTitle
|ftmain = $main";
	//echoAndFlush($addToArticleHistory, 'variable');
	//echoAndFlush($talkPageWikicode, 'variable');
	$newWikicode = insertCodeAtEndOfFirstTemplate($talkPageWikicode, 'Article ?history', $addToArticleHistory);
	if ( $newWikicode == $talkPageWikicode ) {
		throw new giveUpOnThisTopic("On page $talkPageTitle, in {{Article history}} template, unable to determine where to add new actions.");
	}
	//html_var_export($matches, 'variable');
	//echoAndFlush($matches[1], 'variable');
	//echoAndFlush($addToArticleHistory, 'variable');
	//echoAndFlush($matches[2], 'variable');
	return $newWikicode;
}

/** There's a {{GA}} template that some people use instead of {{Article history}}. If this is present, replace it with {{Article history}}. */
function addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle) {
	$hasArticleHistory = preg_match('/\{\{Article ? history([^\}]*)\}\}/i', $talkPageWikicode);
	$gaTemplateWikicode = preg_first_match('/(\{\{GA[^\}]*\}\})/i', $talkPageWikicode);
	//echoAndFlush($gaTemplateWikicode, 'variable');
	if ( ! $hasArticleHistory && $gaTemplateWikicode ) {
		// delete {{ga}} template
		$talkPageWikicode = preg_replace('/\{\{GA[^\}]*\}\}\n/i', '', $talkPageWikicode);
		
		// parse its parameters
		// example: |21:00, 12 March 2017 (UTC)|topic=Sports and recreation|page=1|oldid=769997774
		$parameters = getParametersFromTemplateWikicode($gaTemplateWikicode);
		$date = date('Y-m-d', strtotime($parameters[1]));
		
		// insert {{article history}} template
		$addToTalkPageEndOfLead = 
"{{Article history
|currentstatus = GA
|topic = {$parameters['topic']}

|action1 = GAN
|action1date = $date
|action1link = $talkPageTitle/GA{$parameters['page']}
|action1result = listed
|action1oldid = {$parameters['oldid']}
}}";
		$talkPageWikicode = addToTalkPageEndOfLead($talkPageWikicode, $addToTalkPageEndOfLead);
	}
	return $talkPageWikicode;
}

/** Add wikicode right above the first ==Header== if present, or at bottom of page. */
function addToTalkPageEndOfLead($talkPageWikicode, $wikicodeToAdd) {
	$hasHeadings = strpos($talkPageWikicode, '==');
	if ( $hasHeadings !== false ) {
		$talkPageWikicode = preg_replace('/(?=\n==)/', $wikicodeToAdd . "\n", $talkPageWikicode, 1);
	} else {
		$talkPageWikicode = $talkPageWikicode . "\n" . $wikicodeToAdd;
	}
	//echoAndFlush($talkPageWikicode, 'variable');
	return $talkPageWikicode;
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
	//html_var_export($parameters, 'variable');
	return $parameters;
}

function updateCountPageTopicCount($countPageWikicode, $countPageTitle) {
	$count = preg_first_match("/currently '''([,\d]+)'''/", $countPageWikicode);
	$count = str_replace(',', '', $count); // remove commas
	if ( ! $count ) {
		throw new giveUpOnThisTopic("On page $countPageTitle, unable to find the total topic count.");
	}
	$count++;
	$count = number_format($count); // add commas back
	$countPageWikicode = preg_replace("/(currently ''')([,\d]+)(''')/", '${1}'.$count.'${3}', $countPageWikicode);
	return $countPageWikicode;
}

function updateCountPageArticleCount($countPageWikicode, $countPageTitle, $articlesInTopic) {
	$count = preg_first_match("/encompass '''([,\d]+)'''/", $countPageWikicode);
	$count = str_replace(',', '', $count); // remove commas
	if ( ! $count ) {
		throw new giveUpOnThisTopic("On page $countPageTitle, unable to find the total article count.");
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

function addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $mainArticleTitle) {
	$newWikicode = preg_replace("/('''\[\[Wikipedia:Featured topics\|Topics]] that gained featured status'''.*?)(\|})/s", "$1* [[$topicWikipediaPageTitle|$mainArticleTitle]]\n$2", $goingsOnWikicode);
	if ( $newWikicode == $goingsOnWikicode ) {
		throw new giveUpOnThisTopic("On page $goingsOnTitle, unable to figure out where to insert code.");
	}
	return $newWikicode;
}

function addTopicToNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $mainArticleTitle) {
	$newWikicode = preg_replace("/(<!-- Topics \(15, most recent first\) -->)/", "$1\n* [[$topicWikipediaPageTitle|$mainArticleTitle]]", $newFeaturedContentWikicode);
	if ( $newWikicode == $newFeaturedContentWikicode ) {
		throw new giveUpOnThisTopic("On page $newFeaturedContentTitle, unable to figure out where to insert code.");
	}
	return $newWikicode;
}

function removeBottomTopicFromNewFeaturedContent($newFeaturedContentTitle, $newFeaturedContentWikicode) {
	$wikicode15MostRecentTopics = preg_first_match("/<!-- Topics \(15, most recent first\) -->\n(.*?)<\/div>/s", $newFeaturedContentWikicode);
	$wikicode15MostRecentTopics = trim($wikicode15MostRecentTopics);
	if ( ! $wikicode15MostRecentTopics ) {
		throw new giveUpOnThisTopic("On page $newFeaturedContentTitle, unable to find wikicode for 15 most recent topics.");
	}
	$wikicode15MostRecentTopics = deleteLastLineOfString($wikicode15MostRecentTopics);
	$newWikicode = preg_replace("/(<!-- Topics \(15, most recent first\) -->\n)(.*?)(<\/div>)/s", "$1$wikicode15MostRecentTopics\n\n$3", $newFeaturedContentWikicode);
	if ( $newWikicode == $newFeaturedContentWikicode ) {
		throw new giveUpOnThisTopic("On page $newFeaturedContentTitle, unable to delete oldest topic.");
	}
	return $newWikicode;
}

function getGoodArticleCount($topicBoxWikicode) {
	preg_match_all('/{{\s*(?:class)?icon\s*\|\s*(?:GA)\s*}}/i', $topicBoxWikicode, $matches);
	$count = count($matches[0]);
	//echoAndFlush(var_export($matches, true), 'variable');
	echoAndFlush($count, 'variable');
	return $count;
}

function getFeaturedArticleCount($topicBoxWikicode) {
	preg_match_all('/{{\s*(?:class)?icon\s*\|\s*(?:FA|FL)\s*}}/i', $topicBoxWikicode, $matches);
	$count = count($matches[0]);
	//echoAndFlush(var_export($matches, true), 'variable');
	echoAndFlush($count, 'variable');
	return $count;
}

/*
function addHeadingIfNeeded($talkPageWikicode, $talkPageTitle) {
	$newWikicode = $talkPageWikicode;
	$hasHeadings = ( strpos($talkPageWikicode, '==') !== false );
	$hasTranscludedGA1Page = ( strpos($talkPageWikicode, '/GA1}}') !== false );
	if ( ! $hasHeadings && $hasTranscludedGA1Page ) {
		$newWikicode = preg_replace("/(\{\{Talk:[^\/]+\/GA1}}\s*)$/i", "== Good article ==\n$1", $newWikicode);
		if ( $newWikicode == $talkPageWikicode ) {
			throw new giveUpOnThisTopic("On page $talkPageTitle, unable to add a heading above {{Talk:foobar/GA1}}");
		}
	}
	return $newWikicode;
}
*/

function writeSuccessOrError($nominationPageWikicode, $nominationPageTitle) {
	
}

/** In the {{Featured topic box}} template, makes sure that it has the parameter view=yes. For example, {{Featured topic box|view=yes}} */
function setTopicBoxViewParamterToYes($topicBoxWikicode) {
	$hasViewYes = preg_match('/\|\s*view\s*=\s*yes\s*[\|\}]/si', $topicBoxWikicode);
	if ( $hasViewYes ) return $topicBoxWikicode;
	// delete view = anything
	$topicBoxWikicode = preg_replace('/\|\s*view\s*=[^\|\}]*([\|\}])/si', '$1', $topicBoxWikicode);
	// if the template ended up as {{Template\n}}, get rid of the \n
	$topicBoxWikicode = preg_replace('/({{.*)\n{1,}(}})/i', '$1$2', $topicBoxWikicode);
	// add view = yes
	$topicBoxWikicode = insertCodeAtEndOfFirstTemplate($topicBoxWikicode, 'Featured topic box', '|view=yes');
	return $topicBoxWikicode;
}

/** In the {{Featured topic box}} template, makes sure that if the title parameter has something like |title=''Meet the Who 2'', that the '' is removed so that the "discuss" link isn't broken. */
function cleanTopicBoxTitleParameter($topicBoxWikicode) {
	return preg_replace("/(\|\s*title\s*=\s*)''([^\|\}]*)''(\s*[\|\}])/is", '$1$2$3', $topicBoxWikicode);
}