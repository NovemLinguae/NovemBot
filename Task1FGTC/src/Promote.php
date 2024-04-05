<?php

class Promote {
	protected $eh;
	protected $h;

	public function __construct( EchoHelper $eh, Helper $h ) {
		$this->eh = $eh;
		$this->h = $h;
	}

	public function abortIfAddToTopic( $callerPageWikicode, $title ) {
		preg_match( '/\{\{Add to topic/i', $callerPageWikicode, $matches );
		if ( $matches ) {
			throw new GiveUpOnThisTopic( "On page $title, {{t|Add to topic}} is present, indicating that this is an addition of articles to an existing topic rather than a brand new topic. Bot can only create brand new topics at this time." );
		}
	}

	/**
	 * Abort if 1) {{User:NovemBot/Promote}} is missing or 2) {{User:NovemBot/Promote|done=yes}}
	 */
	public function abortIfPromotionTemplateMissingOrDone( $wikicode, $title ) {
		$matches = stripos( $wikicode, '{{User:NovemBot/Promote}}' );
		if ( $matches === false ) {
			throw new GiveUpOnThisTopic( "On page $title, could not find {{t|User:NovemBot/Promote}}." );
		}
	}

	public function getTopicBoxWikicode( $callerPageWikicode, $title ) {
		$wikicode = $this->h->sliceFirstTemplateFound( $callerPageWikicode, 'good topic box' );
		if ( $wikicode ) {
			return $wikicode;
		}
		$wikicode = $this->h->sliceFirstTemplateFound( $callerPageWikicode, 'featured topic box' );
		if ( $wikicode ) {
			return $wikicode;
		}
		throw new GiveUpOnThisTopic( "On page $title, {{t|Good/featured topic box}} not found." );
	}

	/** This is differen than getTopicTitle(). This is needed to figure out the main article's title. */
	public function getMainArticleTitle( $topicBoxWikicode, $title ) {
		// TODO: handle piped links
		preg_match( "/\|\s*lead\s*=\s*{{\s*(?:class)?icon\s*\|\s*(?:FA|GA|FL)\s*}}\s*(?:'')?\[\[([^\]\|]*)/i", $topicBoxWikicode, $matches );
		if ( !$matches ) {
			throw new GiveUpOnThisTopic( "On page $title, could not find main article name in {{t|Good/Featured topic box}}." );
		}
		$mainArticleTitle = trim( $matches[1] );
		return $mainArticleTitle;
	}

	/** There's 3 sources we can pick the topic name from: 1) main article's title, 2) |title= parameter, 3) /archive page's title. Per a conversation with Aza24, we will get it from #2: the |title= parameter. */
	public function getTopicTitle( $topicBoxWikicode, $mainArticleTitle ) {
		if ( $mainArticleTitle == '' ) {
			throw new Exception( '$mainArticleTitle should not be blank. This was checked in another step.' );
		}

		// search for |title= parameter
		preg_match( "/\|\s*title\s*=\s*([^\|\}]+)\s*/is", $topicBoxWikicode, $matches );

		if ( $matches ) {
			$matches[1] = trim( $matches[1] );
			// get rid of apostrophes
			$matches[1] = str_replace( "'''", "", $matches[1] );
			$matches[1] = str_replace( "''", "", $matches[1] );
			// if getting rid of apostrophes and trimming didn't delete the entire title, return that
			if ( $matches[1] ) {
				return $matches[1];
			}
		}

		// else, return $mainArticleTitle as topicTitle
		return $mainArticleTitle;
	}

	/** It's OK if this one isn't able to find anything. Not a critical error. It can return blank. */
	public function getTopicDescriptionWikicode( $callerPageWikicode ) {
		preg_match( '/===(\n.*?)\{\{(?:Featured topic box|Good topic box)/si', $callerPageWikicode, $matches );
		$output = $matches ? $matches[1] : '';
		if ( $output ) {
			$output = str_replace( '<!---<noinclude>--->', '', $output );
			$output = str_replace( '<!---</noinclude>--->', '', $output );
			$output = str_replace( '<noinclude>', '', $output );
			$output = str_replace( '</noinclude>', '', $output );
			$output = trim( $output );
			$output = '<noinclude>' . $output . '</noinclude>';
		}
		return $output;
	}

	public function getTopicWikipediaPageTitle( $topicTitle ) {
		return "Wikipedia:Featured topics/$topicTitle";
	}

	public function getTopicWikipediaPageWikicode( $topicDescriptionWikicode, $topicBoxWikicode ) {
		// Put only one line break. More than one line break causes excess whitespace when the page is transcluded into other pages in step 6.
		$output = trim( $topicDescriptionWikicode . "\n" . $topicBoxWikicode );
		return $output;
	}

	public function getDatetime() {
		$date = date( 'H:m, j F Y' );
		return $date;
	}

	public function getAllArticleTitles( $topicBoxWikicode, $title ) {
		// Confirmed that it's just FA, GA, FL. There won't be any other icons.
		preg_match_all( "/{{\s*(?:class)?icon\s*\|\s*(?:FA|GA|FL)\}\}\s*(.*)\s*$/im", $topicBoxWikicode, $matches );
		if ( !$matches[1] ) {
			throw new GiveUpOnThisTopic( "On page $title, could not find list of topics inside of {{t|Featured topic box}}." );
		}
		$listOfTitles = $matches[1];
		$this->eh->html_var_export( $listOfTitles, 'variable' );

		// parse each potential title
		foreach ( $listOfTitles as $key => $title2 ) {
			// throw an error if any of the article names are templates, or not article links
			if ( strpos( $title2, '{' ) !== false ) {
				throw new GiveUpOnThisTopic( "On page $title, when parsing the list of topics in {{t|featured topic box}}, found some templates. Try subst:-ing them, then re-running the bot." );
			}

			// get rid of wikilink syntax around it
			$match = $this->h->preg_first_match( '/\[\[([^\|\]]*)(?:\|[^\|\]]*)?\]\]/is', $title2 );
			if ( !$match ) {
				throw new GiveUpOnThisTopic( "On page $title, when parsing the list of topics in {{t|featured topic box}}, found an improperly formatted title. No wikilink found." );
			}
			$listOfTitles[$key] = $match;

			// convert &#32; to space. fixes an issue with subst-ing ship templates such as {{ship}} and {{sclass}}
			$listOfTitles[$key] = preg_replace( '/&#32;/', ' ', $listOfTitles[$key] );

			// trim
			$listOfTitles[$key] = trim( $listOfTitles[$key] );
		}

		$this->eh->html_var_export( $listOfTitles, 'variable' );
		return $listOfTitles;
	}

	public function makeTopicTalkPageWikicode( $mainArticleTitle, $topicTitle, $nonMainArticleTitles, $goodOrFeatured, $datetime, $wikiProjectBanners, $nominationPageTitle ) {
		if ( $goodOrFeatured !== 'good' && $goodOrFeatured !== 'featured' ) {
			throw new Exception( '$goodOrFeatured must equal good or featured' );
		}
		$nonMainArticleTitlestring = '';
		$count = 1;
		$lastArticleNumber = count( $nonMainArticleTitles );
		foreach ( $nonMainArticleTitles as $key => $value ) {
			$and = '';
			if ( $count == $lastArticleNumber ) {
				$and = ' and';
			}
			$nonMainArticleTitlestring .= ",$and [[$value]]";
			$count++;
		}
		$actionCode = ( $goodOrFeatured == 'good' ) ? 'GTC' : 'FTC';
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

	public function getTopicTalkPageTitle( $topicTitle ) {
		return 'Wikipedia talk:Featured topics/' . $topicTitle;
	}

	public function getWikiProjectBanners( $mainArticleTalkPageWikicode, $title ) {
		// Match WikiProject banners
		// Do not match template parameters such as |class=GA|importance=Low
		// We will have to add }} to the end of the matches later
		preg_match_all( '/\{\{(WikiProject (?!banner|shell)[^\|\}]*)/i', $mainArticleTalkPageWikicode, $matches );
		if ( !$matches ) {
			throw new GiveUpOnThisTopic( "On page $title, could not find WikiProject banners on main article's talk page." );
		}
		$bannerWikicode = '';
		foreach ( $matches[0] as $key => $value ) {
			$bannerWikicode .= trim( $value ) . "}}\n";
		}
		// chop off last \n
		$bannerWikicode = substr( $bannerWikicode, 0, -1 );
		if ( count( $matches[0] ) > 1 ) {
			$bannerWikicode = "{{WikiProject banner shell|1=\n" . $bannerWikicode . "\n}}";
		}
		return $bannerWikicode;
	}

	public function getNonMainArticleTitles( $allArticleTitles, $mainArticleTitle ) {
		return $this->h->deleteArrayValue( $allArticleTitles, $mainArticleTitle );
	}

	public function abortIfTooManyArticlesInTopic( $allArticleTitles, $MAX_ARTICLES_ALLOWED_IN_TOPIC, $title ) {
		if ( count( $allArticleTitles ) > $MAX_ARTICLES_ALLOWED_IN_TOPIC ) {
			throw new GiveUpOnThisTopic( "On page $title, too many topics in the topic box." );
		}
	}

	public function removeGTCFTCTemplate( $talkPageWikicode ) {
		return preg_replace( '/\{\{(?:gtc|ftc)[^\}]*\}\}\n/i', '', $talkPageWikicode );
	}

	/** Determine next |action= number in {{Article history}} template. This is so we can insert an action. */
	public function determineNextActionNumber( $talkPageWikicode, $ARTICLE_HISTORY_MAX_ACTIONS, $talkPageTitle ) {
		$isRedirect = preg_match( "/^\s*#redirect/i", $talkPageWikicode );
		if ( $isRedirect ) {
			throw new GiveUpOnThisTopic( "On page $talkPageTitle, the page is a redirect. Please update {{Featured topic box}} to not point at redirect pages." );
		}

		// Earlier steps should have converted any {{GA}} templates to {{Article history}}. If there's no {{Article history}} template, then there were no {{GA}} templates earlier. Either way, it's a problem.
		$hasArticleHistory = preg_match( "/{{Article ?history/i", $talkPageWikicode );
		if ( !$hasArticleHistory ) {
			throw new GiveUpOnThisTopic( "On page $talkPageTitle, no {{GA}} and no {{Article history}} templates were found. One of these templates is required." );
		}

		for ( $i = $ARTICLE_HISTORY_MAX_ACTIONS; $i >= 1; $i-- ) {
			$hasAction = preg_match( "/\|\s*action$i\s*=/i", $talkPageWikicode );
			if ( $hasAction ) {
				return $i + 1;
			}
		}
		throw new GiveUpOnThisTopic( "On page $talkPageTitle, in {{t|Article history}} template, unable to determine next |action= number." );
	}

	public function updateArticleHistory( $talkPageWikicode, $nextActionNumber, $goodOrFeatured, $datetime, $mainArticleTitle, $topicTitle, $articleTitle, $talkPageTitle, $nominationPageTitle, $oldid ) {
		if ( $goodOrFeatured !== 'good' && $goodOrFeatured !== 'featured' ) {
			throw new Exception( '$goodOrFeatured must equal good or featured' );
		}
		$main = ( $mainArticleTitle == $articleTitle ) ? 'yes' : 'no';
		$ftcOrGTC = ( $goodOrFeatured == 'featured' ) ? 'FTC' : 'GTC';
		$nextFTNumber = $this->getNextFTNumber( $talkPageWikicode );
		$addToArticleHistory =
"|action$nextActionNumber = $ftcOrGTC
|action{$nextActionNumber}date = $datetime
|action{$nextActionNumber}link = $nominationPageTitle
|action{$nextActionNumber}result = promoted
|action{$nextActionNumber}oldid = $oldid
|ft{$nextFTNumber}name = $topicTitle
|ft{$nextFTNumber}main = $main";
		$newWikicode = $this->h->insertCodeAtEndOfFirstTemplate( $talkPageWikicode, 'Article ?history', $addToArticleHistory );
		if ( $newWikicode == $talkPageWikicode ) {
			throw new GiveUpOnThisTopic( "On page $talkPageTitle, in {{t|Article history}} template, unable to determine where to add new actions." );
		}
		return $newWikicode;
	}

	/**
	 * @return string|int $ftNumber '' if the next FT number is 1, or the number if the next FT Number is 2+
	 */
	public function getNextFTNumber( $talkPageWikicode ) {
		// check ftname
		$hasFTName = preg_match( "/\|\s*ftname\s*=/", $talkPageWikicode, $matches );
		if ( !$hasFTName ) {
			return '';
		}

		// check ft2name, ft3name, etc.
		$count = 2;
		while ( true ) {
			$hasFTName = preg_match( "/\|\s*ft{$count}name\s*=/", $talkPageWikicode, $matches );
			if ( $hasFTName ) {
				$count++;
			} else {
				break;
			}
		}
		return $count;
	}

	/** There's a {{GA}} template that some people use instead of {{Article history}}. If this is present, replace it with {{Article history}}. */
	public function addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle ) {
		$hasArticleHistory = preg_match( '/\{\{Article ?history([^\}]*)\}\}/i', $talkPageWikicode );
		$gaTemplateWikicode = $this->h->preg_first_match( '/(\{\{GA\b[^\}]*\}\})/i', $talkPageWikicode );
		if ( !$hasArticleHistory && $gaTemplateWikicode ) {
			// delete {{ga}} template
			$talkPageWikicode = preg_replace( '/\{\{GA\b[^\}]*\}\}\n?/i', '', $talkPageWikicode );
			$talkPageWikicode = trim( $talkPageWikicode );

			// parse its parameters
			// example: |21:00, 12 March 2017 (UTC)|topic=Sports and recreation|page=1|oldid=769997774
			$parameters = $this->getParametersFromTemplateWikicode( $gaTemplateWikicode );

			// if no page specified, assume page is 1. so then the good article review link will be parsed as /GA1
			if ( !isset( $parameters['page'] ) || !$parameters['page'] ) {
				$parameters['page'] = 1;
			}

			$topicString = '';
			if ( isset( $parameters['topic'] ) ) {
				$topicString = "\n|topic = {$parameters['topic']}";
			// subtopic is an alias only used in {{ga}}, it is not used in {{article history}}
			} elseif ( isset( $parameters['subtopic'] ) ) {
				$topicString = "\n|topic = {$parameters['subtopic']}";
			}

			$oldIdString = '';
			if ( isset( $parameters['oldid'] ) ) {
				$oldIdString = "\n|action1oldid = " . $parameters['oldid'];
			}

			$date = date( 'Y-m-d', strtotime( $parameters[1] ) );

			// insert {{article history}} template
			$addToTalkPageAboveWikiProjects =
"{{Article history
|currentstatus = GA"
			. $topicString . "

|action1 = GAN
|action1date = $date
|action1link = $talkPageTitle/GA{$parameters['page']}
|action1result = listed"
			. $oldIdString . "
}}\n";
			$talkPageWikicode = $this->addToTalkPageAboveWikiProjects( $talkPageWikicode, $addToTalkPageAboveWikiProjects );
		}
		return $talkPageWikicode;
	}

	/** Add wikicode right above {{WikiProject X}} or {{WikiProject Banner Shell}} if present, or first ==Header== if present, or at bottom of page. Treat {{Talk:abc/GA1}} as a header. */
	public function addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd ) {
		if ( !$talkPageWikicode ) {
			return $wikicodeToAdd;
		}

		// Find first WikiProject or WikiProject banner shell template
		$wikiProjectLocation = false;
		$dictionary = [ 'wikiproject', 'wpb', 'wpbs', 'wpbannershell', 'wp banner shell', 'bannershell', 'scope shell', 'project shell', 'multiple wikiprojects', 'football' ];
		foreach ( $dictionary as $key => $value ) {
			// case insensitive
			$location = stripos( $talkPageWikicode, '{{' . $value );
			if ( $location !== false ) {
				// if this location is higher up than the previous found location, overwrite it
				if ( $wikiProjectLocation === false || $wikiProjectLocation > $location ) {
					$wikiProjectLocation = $location;
				}
			}
		}

		// Find first heading
		$headingLocation = strpos( $talkPageWikicode, '==' );

		// Find first {{Talk:abc/GA1}} template
		$gaTemplateLocation = $this->h->preg_position( '/{{[^\}]*\/GA\d{1,2}}}/is', $talkPageWikicode );

		// Set insert location
		if ( $wikiProjectLocation !== false ) {
			$insertPosition = $wikiProjectLocation;
		} elseif ( $headingLocation !== false ) {
			$insertPosition = $headingLocation;
		} elseif ( $gaTemplateLocation !== false ) {
			$insertPosition = $gaTemplateLocation;
		} else {
			// insert at end of page
			$insertPosition = strlen( $talkPageWikicode );
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
		$pos = $insertPosition <= 0 ? 0 : $insertPosition - 1;
		$i = 1;
		while ( $pos != 0 ) {
			$char = substr( $talkPageWikicode, $pos, 1 );
			if ( $char == "\n" ) {
				// skip first two \n, those are OK to keep
				if ( $i != 1 && $i != 2 ) {
					$deleteTopPosition = $pos;
					if ( $i == 3 ) {
						$deleteBottomPosition = $insertPosition;
					}
				}
				// insert position should back up past all \n's.
				$insertPosition = $pos;
				$i++;
				$pos--;
			} else {
				break;
			}
		}
		if ( $deleteTopPosition !== false ) {
			$talkPageWikicode = $this->h->deleteMiddleOfString( $talkPageWikicode, $deleteTopPosition, $deleteBottomPosition );
		}

		$lengthOfRightHalf = strlen( $talkPageWikicode ) - $insertPosition;
		$leftHalf = substr( $talkPageWikicode, 0, $insertPosition );
		$rightHalf = substr( $talkPageWikicode, $insertPosition, $lengthOfRightHalf );

		if ( $insertPosition == 0 ) {
			return $wikicodeToAdd . "\n" . $talkPageWikicode;
		} else {
			return $leftHalf . "\n" . $wikicodeToAdd . $rightHalf;
		}
	}

	public function getParametersFromTemplateWikicode( $wikicode ) {
		// remove {{ and }}
		$wikicode = substr( $wikicode, 2, -2 );
		// TODO: explode without exploding | inside of inner templates
		$strings = explode( '|', $wikicode );
		$parameters = [];
		$unnamedParameterCount = 1;
		$i = 0;
		foreach ( $strings as $key => $string ) {
			$i++;
			if ( $i == 1 ) {
				// skip the template name, this is not a parameter
				continue;
			}
			$hasEquals = strpos( $string, '=' );
			if ( $hasEquals === false ) {
				$parameters[$unnamedParameterCount] = $string;
				$unnamedParameterCount++;
			} else {
				// isolate param name and param value by looking for first equals sign
				preg_match( '/^([^=]*)=(.*)$/s', $string, $matches );
				$paramName = strtolower( trim( $matches[1] ) );
				$paramValue = trim( $matches[2] );
				$parameters[$paramName] = $paramValue;
			}
		}
		return $parameters;
	}

	public function updateCountPageTopicCount( $countPageWikicode, $countPageTitle ) {
		$count = $this->h->preg_first_match( "/currently '''([,\d]+)'''/", $countPageWikicode );
		// remove commas
		$count = str_replace( ',', '', $count );
		if ( !$count ) {
			throw new GiveUpOnThisTopic( "On page $countPageTitle, unable to find the total topic count." );
		}
		$count++;
		// add commas back
		$count = number_format( $count );
		$countPageWikicode = preg_replace( "/(currently ''')([,\d]+)(''')/", '${1}' . $count . '${3}', $countPageWikicode );
		return $countPageWikicode;
	}

	public function updateCountPageArticleCount( $countPageWikicode, $countPageTitle, $articlesInTopic ) {
		$count = $this->h->preg_first_match( "/encompass '''([,\d]+)'''/", $countPageWikicode );
		// remove commas
		$count = str_replace( ',', '', $count );
		if ( !$count ) {
			throw new GiveUpOnThisTopic( "On page $countPageTitle, unable to find the total article count." );
		}
		$count += $articlesInTopic;
		// add commas back
		$count = number_format( $count );
		$countPageWikicode = preg_replace( "/(encompass ''')([,\d]+)(''')/", '${1}' . $count . '${3}', $countPageWikicode );
		return $countPageWikicode;
	}

	public function getLogPageTitle( $datetime, $goodOrFeatured ) {
		$goodOrFeatured = ucfirst( $goodOrFeatured );
		$monthAndYear = date( 'F Y', strtotime( $datetime ) );
		return "Wikipedia:Featured and good topic candidates/$goodOrFeatured log/$monthAndYear";
	}

	public function addTopicToGoingsOn( $goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $topicTitle, $timestamp ) {
		// gmdate = UTC
		$date = date( 'j M', $timestamp );
		// list type to use: * not #, per example: https://en.wikipedia.org/wiki/Wikipedia:Goings-on/November_29,_2020. Although it looks like EnterpriseyBot has switched to # for the articles section. The lists and pictures sections have remained *.
		$newWikicode = preg_replace( "/('''\[\[Wikipedia:Featured topics\|Topics]] that gained featured status'''.*?)(\|})/s", "$1* [[$topicWikipediaPageTitle|$topicTitle]] ($date)\n$2", $goingsOnWikicode );
		if ( $newWikicode == $goingsOnWikicode ) {
			throw new GiveUpOnThisTopic( "On page $goingsOnTitle, unable to figure out where to insert code." );
		}
		return $newWikicode;
	}

	public function addTopicToNewFeaturedContent( $newFeaturedContentTitle, $newFeaturedContentWikicode, $topicWikipediaPageTitle, $topicTitle ) {
		$newWikicode = preg_replace( "/(<!-- Topics \(15, most recent first\) -->)/", "$1\n* [[$topicWikipediaPageTitle|$topicTitle]]", $newFeaturedContentWikicode );
		if ( $newWikicode == $newFeaturedContentWikicode ) {
			throw new GiveUpOnThisTopic( "On page $newFeaturedContentTitle, unable to figure out where to insert code." );
		}
		return $newWikicode;
	}

	public function removeBottomTopicFromNewFeaturedContent( $newFeaturedContentTitle, $newFeaturedContentWikicode ) {
		$wikicode15MostRecentTopics = $this->h->preg_first_match( "/<!-- Topics \(15, most recent first\) -->\n(.*?)<\/div>/s", $newFeaturedContentWikicode );
		$wikicode15MostRecentTopics = trim( $wikicode15MostRecentTopics );
		if ( !$wikicode15MostRecentTopics ) {
			throw new GiveUpOnThisTopic( "On page $newFeaturedContentTitle, unable to find wikicode for 15 most recent topics." );
		}
		$wikicode15MostRecentTopics = $this->h->deleteLastLineOfString( $wikicode15MostRecentTopics );
		$newWikicode = preg_replace( "/(<!-- Topics \(15, most recent first\) -->\n)(.*?)(<\/div>)/s", "$1$wikicode15MostRecentTopics\n\n$3", $newFeaturedContentWikicode );
		if ( $newWikicode == $newFeaturedContentWikicode ) {
			throw new GiveUpOnThisTopic( "On page $newFeaturedContentTitle, unable to delete oldest topic." );
		}
		return $newWikicode;
	}

	public function getGoodArticleCount( $topicBoxWikicode ) {
		preg_match_all( '/{{\s*(?:class)?icon\s*\|\s*(?:GA)\s*}}/i', $topicBoxWikicode, $matches );
		$count = count( $matches[0] );
		$this->eh->echoAndFlush( $count, 'variable' );
		return $count;
	}

	public function getFeaturedArticleCount( $topicBoxWikicode ) {
		preg_match_all( '/{{\s*(?:class)?icon\s*\|\s*(?:FA|FL)\s*}}/i', $topicBoxWikicode, $matches );
		$count = count( $matches[0] );
		$this->eh->echoAndFlush( $count, 'variable' );
		return $count;
	}

	public function markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured ) {
		// Change {{User:NovemBot/Promote}} to include |done=yes, which will prevent the bot from going into an endless loop every hour.
		$nominationPageWikicode2 = preg_replace( '/({{\s*User:NovemBot\/Promote\s*)(}}.*?\(UTC\))/is', "$1|done=yes$2", $nominationPageWikicode );
		if ( $nominationPageWikicode == $nominationPageWikicode2 ) {
			throw new GiveUpOnThisTopic( "On page $nominationPageTitle, unable to find {{t|User:NovemBot/Promote}} template and signature." );
		}

		$res = $this->splitWikicodeIntoWikicodeAndCategories( $nominationPageWikicode2 );
		$wikicodeNoCategories = $res[ 'wikicodeNoCategories' ];
		$wikicodeCategories = $res[ 'wikicodeCategories' ];

		$pageToAddTo = ( $goodOrFeatured == 'good' ) ? '[[Wikipedia:Good topics]]' : '[[Wikipedia:Featured topics]]';
		$wikicodeNoCategories = trim( $wikicodeNoCategories ) . "\n* {{Done}}. Promotion completed successfully. Don't forget to add <code><nowiki>{{{$topicWikipediaPageTitle}}}</nowiki></code> to the appropriate section of $pageToAddTo. ~~~~";

		// Add {{Fa top}} and {{Fa bottom}}
		// $wikicodeNoCategories = "{{Fa top}}\n" . trim( $wikicodeNoCategories ) . "\n{{Fa bottom}}\n";

		// Add categories back
		if ( $wikicodeNoCategories && $wikicodeCategories ) {
			$nominationPageWikicode3 = $wikicodeNoCategories . "\n" . $wikicodeCategories;
		} else {
			$nominationPageWikicode3 = $wikicodeNoCategories . $wikicodeCategories;
		}

		return $nominationPageWikicode3;
	}

	/**
	 * split wikicode variable in half. goal is to put the bottom categories into its own variable, so we can do stuff like hat the rest of the wikicode or add a comment to the bulleted list, without messing up the position of the categories
	 */
	public function splitWikicodeIntoWikicodeAndCategories( $wikicode ) {
		$lineStartKeywords = [
			'<noinclude',
			'[[Category',
			'[[category',
			'</noinclude',
		];
		$lines = explode( "\n", $wikicode );
		$lineCount = count( $lines );
		for ( $i = $lineCount - 1; $i >= 0; $i-- ) {
			$line = $lines[ $i ];
			// lines starting with our keywords go in the "wikicodeCategories" section
			foreach ( $lineStartKeywords as $keyword ) {
				if ( str_starts_with( $line, $keyword ) ) {
					continue 2;
				}
			}
			// so do blank lines
			if ( $line === '' ) {
				continue;
			}
			// iterating from the bottom line up, once we encounter a line that does not meet any of our criteria, we're done building our "wikicodeCategories" variable. the rest goes in the "wikicodeNoCategories" variable
			break;
		}
		$ret[ 'wikicodeNoCategories' ] = implode( "\n", array_slice( $lines, 0, $i + 1 ) );
		$ret[ 'wikicodeCategories' ] = implode( "\n", array_slice( $lines, $i + 1 ) );
		return $ret;
	}

	public function markError( $nominationPageWikicode, $nominationPageTitle, $errorMessage ) {
		// toggle the original summoning code to done=yes, so that this page doesn't get processed over and over again, and so this error doesn't get written over and over again
		$nominationPageWikicode = preg_replace( '/({{\s*User:NovemBot\/Promote\s*)(}}.*?\(UTC\))/is', "$1|done=yes$2", $nominationPageWikicode );

		// TODO: also look for and remove [[Category: blah blah]]
		// TODO: if neither of those changes resulted in a change to the Wikitext, throw a fatal error, to prevent a loop where the bot writes an error message every hour

		// add an error message to the summoning page
		$nominationPageWikicode .= "\n* {{N.b.}} There was an issue that prevented the promotion bot from promoting this topic. Please solve the issue and run the bot again. The error description is: <code><nowiki>$errorMessage</nowiki></code> ~~~~";

		return $nominationPageWikicode;
	}

	/** In the {{Featured topic box}} template, makes sure that it has the parameter view=yes. For example, {{Featured topic box|view=yes}} */
	public function setTopicBoxViewParameterToYes( $topicBoxWikicode ) {
		$hasViewYes = preg_match( '/\|\s*view\s*=\s*yes\s*[\|\}]/si', $topicBoxWikicode );
		if ( $hasViewYes ) {
			return $topicBoxWikicode;
		}
		// delete view = anything
		$topicBoxWikicode = preg_replace( '/\|\s*view\s*=[^\|\}]*([\|\}])/si', '$1', $topicBoxWikicode );
		// if the template ended up as {{Template\n}}, get rid of the \n
		$topicBoxWikicode = preg_replace( '/({{.*)\n{1,}(}})/i', '$1$2', $topicBoxWikicode );
		// add view = yes
		$topicBoxWikicode = $this->h->insertCodeAtEndOfFirstTemplate( $topicBoxWikicode, 'Featured topic box', '|view=yes' );
		return $topicBoxWikicode;
	}

	/** In the {{Featured topic box}} template, makes sure it has a |title=. Else the discuss link will be red. */
	public function setTopicBoxTitleParameter( $topicBoxWikicode, $mainArticleTitle ) {
		$hasBlankTitleParameter = preg_match( '/(\|\s*title\s*=)(\s*)([\|\}])/is', $topicBoxWikicode );
		$hasTitleParameter = preg_match( '/\|\s*title\s*=/is', $topicBoxWikicode );
		if ( $hasBlankTitleParameter ) {
			return preg_replace( '/(\|\s*title\s*=)(\s*)([\|\}])/is', "$1$mainArticleTitle$3", $topicBoxWikicode );
		// if |title is not found, append it to the end of the template
		} elseif ( !$hasTitleParameter ) {
			return $this->h->insertCodeAtEndOfFirstTemplate( $topicBoxWikicode, 'featured topic box', "|title=$mainArticleTitle" );
		}
		// else title is already present, do nothing
		return $topicBoxWikicode;
	}

	/** In the {{Featured topic box}} template, makes sure that if the title parameter has something like |title=''Meet the Who 2'', that the '' is removed so that the "discuss" link isn't broken. */
	public function cleanTopicBoxTitleParameter( $topicBoxWikicode ) {
		return preg_replace( "/(\|\s*title\s*=\s*)''([^\|\}]*)''(\s*[\|\}])/is", '$1$2$3', $topicBoxWikicode );
	}

	/** Topic descriptions should not have user signatures. Strip these out. */
	public function removeSignaturesFromTopicDescription( $topicDescriptionWikicode ) {
		return preg_replace( "/ \[\[User:.*\(UTC\)/is", '', $topicDescriptionWikicode );
	}

	/** Takes the wikicode of the page [[Wikipedia:Featured and good topic candidates]], and removes the nomination page from it. For example, if the nomination page title is "Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1", it will remove {{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}} from the page. */
	public function removeTopicFromFGTC( $nominationPageTitle, $fgtcWikicode, $fgtcTitle ) {
		$wikicode2 = str_replace( "{{" . $nominationPageTitle . "}}\n", '', $fgtcWikicode );
		$wikicode2 = str_replace( "\n{{" . $nominationPageTitle . "}}", '', $wikicode2 );
		/* OK if this is missing. No need to throw a fatal error
		if ( $fgtcWikicode == $wikicode2 ) {
			throw new GiveUpOnThisTopic("On page $fgtcTitle, unable to locate {{" . $nominationPageTitle . "}}.");
		}
		*/
		return $wikicode2;
	}

	public function checkCounts( $goodArticleCount, $featuredArticleCount, $allArticleTitles ) {
		if ( $goodArticleCount + $featuredArticleCount <= 0 ) {
			throw new GiveUpOnThisTopic( "Unexpected value for the count of good articles and featured articles in the topic. Sum is 0 or less." );
		}
		if ( $goodArticleCount + $featuredArticleCount != count( $allArticleTitles ) ) {
			throw new GiveUpOnThisTopic( "Unexpected value for the count of good articles and featured articles in the topic. Sum is not equal to the number of articles detected in {{Featured topic box}}." );
		}
		// Good/featured topics should have at least 2 articles. If not, something is wrong.
		if ( count( $allArticleTitles ) < 2 ) {
			throw new GiveUpOnThisTopic( "When parsing the list of topics in {{featured topic box}}, found less than 2 articles." );
		}
	}

	/**
	 * Per https://en.wikipedia.org/wiki/Wikipedia_talk:Featured_and_good_topic_candidates#If_a_topic_has_5_FAs_and_5_GAs,_is_it_a_good_topic_or_a_featured_topic?, 50% = featured topic, not good topic.
	 */
	public function decideIfGoodOrFeatured( $goodArticleCount, $featuredArticleCount ) {
		if ( $featuredArticleCount >= $goodArticleCount ) {
			return 'featured';
		}
		return 'good';
	}

	/**
	 * Updates the wikitext on the count page Template:Featured topic log
	 *
	 * @param string $month Month, fully spelled out, e.g. August
	 * @param string $year Year, four digits, e.g. 2022
	 * @param string $countTemplateWikicode
	 * @param string $goodOrFeatured Must be 'good' or 'featured'
	 * @return string $result Returns $countTemplateWikicode, modified slightly to increment one of the counts
	 * @throws GiveUpOnThisTopic
	 */
	public function getTemplateFeaturedTopicLogWikicode( $month, $year, $countTemplateWikicode, $goodOrFeatured ) {
		$patternFound = preg_match( "/Wikipedia:Featured and good topic candidates\/Featured log\/$month $year\|(\d{1,2})&nbsp;FT,&nbsp;(\d{1,2})/s", $countTemplateWikicode, $matches );
		if ( !$patternFound ) {
			throw new GiveUpOnThisTopic( "When figuring out what to write to Template:Featured topic log, unable to find the table row corresponding to today's month and year." );
		}

		$featuredCount = $matches[1];
		$goodCount = $matches[2];

		if ( $goodOrFeatured === 'featured' ) {
			$featuredCount++;
		} elseif ( $goodOrFeatured === 'good' ) {
			$goodCount++;
		} else {
			throw new GiveUpOnThisTopic( "When figuring out what to write to Template:Featured topic log, invalid value for the variable goodOrFeatured." );
		}

		// write it back
		$result = preg_replace( "/Wikipedia:Featured and good topic candidates\/Featured log\/$month $year\|(\d{1,2})&nbsp;FT,&nbsp;(\d{1,2})/s", "Wikipedia:Featured and good topic candidates/Featured log/$month $year|$featuredCount&nbsp;FT,&nbsp;$goodCount", $countTemplateWikicode );

		$somethingChanged = $result !== $countTemplateWikicode;
		if ( !$somethingChanged ) {
			throw new GiveUpOnThisTopic( "When figuring out what to write to Template:Featured topic log, the generated wikitext contained no changes, which indicates a bug somewhere." );
		}

		return $result;
	}
}
