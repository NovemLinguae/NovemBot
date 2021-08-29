<?php

use PHPUnit\Framework\TestCase;

class PromoteTest extends TestCase {
	function test_getTopicWikipediaPageTitle_dontWriteToWikipediaGoodTopics() {
		$mainArticleTitle = 'TestPage';
		$goodOrFeatured = 'good';
		$result = getTopicWikipediaPageTitle($mainArticleTitle, $goodOrFeatured);
		$this->assertSame('Wikipedia:Featured topics/TestPage', $result);
	}
	
	function test_getWikiProjectBanners_dontAddBannerShellTwice() {
		$title = 'Wikipedia talk:Featured topics/Meet the Woo 2';
		$mainArticleTalkPageWikicode =
'{{WikiProject banner shell|1=
{{WikiProject Albums|class=GA|importance=low}}
{{WikiProject Hip hop|class=GA|importance=low}}
}}';
		$result = getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame(
'{{WikiProject banner shell|1=
{{WikiProject Albums|class=GA|importance=low}}
{{WikiProject Hip hop|class=GA|importance=low}}
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParamterToYes_inputContainsViewYes() {
		$topicBoxWikicode = '{{Featured topic box|view=yes}}';
		$result = setTopicBoxViewParamterToYes($topicBoxWikicode);
		$this->assertSame('{{Featured topic box|view=yes}}', $result);
	}
	
	function test_setTopicBoxViewParamterToYes_inputContainsViewYes2() {
		$topicBoxWikicode = '{{Featured topic box | view = yes }}';
		$result = setTopicBoxViewParamterToYes($topicBoxWikicode);
		$this->assertSame('{{Featured topic box | view = yes }}', $result);
	}
	
	function test_setTopicBoxViewParamterToYes_inputContainsViewYes3() {
		$topicBoxWikicode =
'{{Featured topic box
| view = yes
}}';
		$result = setTopicBoxViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
| view = yes
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParamterToYes_inputContainsViewNo1() {
		$topicBoxWikicode = '{{Featured topic box|view=no}}';
		$result = setTopicBoxViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParamterToYes_inputContainsViewNo2() {
		$topicBoxWikicode =
'{{Featured topic box
| view = no
}}';
		$result = setTopicBoxViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParamterToYes_inputIsJustTemplateName1() {
		$topicBoxWikicode = '{{Featured topic box}}';
		$result = setTopicBoxViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParamterToYes_inputIsJustTemplateName2() {
		$topicBoxWikicode =
'{{Featured topic box

}}';
		$result = setTopicBoxViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_getTopicWikipediaPageWikicode_putOnlyOneLineBreak() {
		$topicDescriptionWikicode = 'a';
		$topicBoxWikicode = 'b';
		$result = getTopicWikipediaPageWikicode($topicDescriptionWikicode, $topicBoxWikicode);
		$this->assertSame("a\nb", $result);
	}
	
	function test_cleanTopicBoxTitleParameter_noApostrophes() {
		$topicBoxWikicode = '{{Featured topic box|title=No changes needed|column1=blah}}';
		$result = cleanTopicBoxTitleParameter($topicBoxWikicode);
		$this->assertSame($topicBoxWikicode, $result);
	}
	
	function test_cleanTopicBoxTitleParameter_apostrophes() {
		$topicBoxWikicode = "{{Featured topic box|title=''Changes needed''|column1=blah}}";
		$result = cleanTopicBoxTitleParameter($topicBoxWikicode);
		$this->assertSame('{{Featured topic box|title=Changes needed|column1=blah}}', $result);
	}
	
	function test_removeSignaturesFromTopicDescription_signature() {
		$topicDescriptionWikicode =
"<noinclude>'''''[[Meet the Woo 2]]''''' is the second mixtape by American rapper [[Pop Smoke]]. It was released on February 7, 2020, less than two weeks before the rapper was shot and killed at the age of 20 during a home invasion in Los Angeles. After many months of bringing all the articles to GA; it is finally ready. [[User:Shoot for the Stars|You know I'm shooting for the stars, aiming for the moon 💫]] ([[User talk:Shoot for the Stars|talk]]) 08:25, 26 May 2021 (UTC)</noinclude>";
		$result = removeSignaturesFromTopicDescription($topicDescriptionWikicode);
		$this->assertSame(
"<noinclude>'''''[[Meet the Woo 2]]''''' is the second mixtape by American rapper [[Pop Smoke]]. It was released on February 7, 2020, less than two weeks before the rapper was shot and killed at the age of 20 during a home invasion in Los Angeles. After many months of bringing all the articles to GA; it is finally ready.</noinclude>"
		, $result);
	}
	
	function test_removeSignaturesFromTopicDescription_noSignature() {
		$topicDescriptionWikicode =
"<!---<noinclude>--->The [[EFL League One play-offs]] are a series of play-off matches contested by the association football teams finishing from third to sixth in [[EFL League One]], the third tier of English football, and are part of the [[English Football League play-offs]]. As of 2021, the play-offs comprise two semi-finals, where the team finishing third plays the team finishing sixth, and the team finishing fourth plays the team finishing fifth, each conducted as a two-legged tie. The winners of the semi-finals progress to the final which is contested at [[Wembley Stadium]].<!---</noinclude>--->";
		$result = removeSignaturesFromTopicDescription($topicDescriptionWikicode);
		$this->assertSame($topicDescriptionWikicode, $result);
	}
	
	function test_removeTopicFromFGTC_middleOfPage() {
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1';
		$fgtcWikicode = 
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}';
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$result = removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
		$this->assertSame(
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}'
		, $result);
	}
	
	function test_removeTopicFromFGTC_lastLineOnPage() {
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1';
		$fgtcWikicode = 
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}
{{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}}';
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$result = removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
		$this->assertSame(
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}'
		, $result);
	}
	
	function test_removeTopicFromFGTC_firstLineOnPage() {
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1';
		$fgtcWikicode = 
'{{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}}
{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}';
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$result = removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
		$this->assertSame(
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}'
		, $result);
	}
	
	function test_addArticleHistoryIfNotPresent_gaTemplateWithNoPage() {
		$talkPageWikicode = '{{GA|00:03, 5 January 2021 (UTC)|topic=Sports and recreation|page=|oldid=998352580}}';
		$talkPageTitle = 'Talk:History of Burnley F.C.';
		$result = addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
		$this->assertSame(
'{{Article history
|currentstatus = GA
|topic = Sports and recreation

|action1 = GAN
|action1date = 2021-01-05
|action1link = Talk:History of Burnley F.C./GA1
|action1result = listed
|action1oldid = 998352580
}}'
		, $result);
	}
}