<?php

use PHPUnit\Framework\TestCase;

class PromoteTest extends TestCase {
	function setUp(): void {
		// stub EchoHelper so that it doesn't echo
		$eh = $this->createStub(EchoHelper::class);
		
		$h = new Helper();
		$this->p = new Promote($eh, $h);
	}

	// TODO: add @group for grouping. this is equivlent to Jest's "describe"

	function test_getTopicWikipediaPageTitle_dontWriteToWikipediaGoodTopics() {
		$mainArticleTitle = 'TestPage';
		$goodOrFeatured = 'good';
		$result = $this->p->getTopicWikipediaPageTitle($mainArticleTitle, $goodOrFeatured);
		$this->assertSame('Wikipedia:Featured topics/TestPage', $result);
	}
	
	function test_setTopicBoxViewParameterToYes_inputContainsViewYes() {
		$topicBoxWikicode = '{{Featured topic box|view=yes}}';
		$result = $this->p->setTopicBoxViewParameterToYes($topicBoxWikicode);
		$this->assertSame('{{Featured topic box|view=yes}}', $result);
	}
	
	function test_setTopicBoxViewParameterToYes_inputContainsViewYes2() {
		$topicBoxWikicode = '{{Featured topic box | view = yes }}';
		$result = $this->p->setTopicBoxViewParameterToYes($topicBoxWikicode);
		$this->assertSame('{{Featured topic box | view = yes }}', $result);
	}
	
	function test_setTopicBoxViewParameterToYes_inputContainsViewYes3() {
		$topicBoxWikicode =
'{{Featured topic box
| view = yes
}}';
		$result = $this->p->setTopicBoxViewParameterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
| view = yes
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParameterToYes_inputContainsViewNo1() {
		$topicBoxWikicode = '{{Featured topic box|view=no}}';
		$result = $this->p->setTopicBoxViewParameterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParameterToYes_inputContainsViewNo2() {
		$topicBoxWikicode =
'{{Featured topic box
| view = no
}}';
		$result = $this->p->setTopicBoxViewParameterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParameterToYes_inputIsJustTemplateName1() {
		$topicBoxWikicode = '{{Featured topic box}}';
		$result = $this->p->setTopicBoxViewParameterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTopicBoxViewParameterToYes_inputIsJustTemplateName2() {
		$topicBoxWikicode =
'{{Featured topic box

}}';
		$result = $this->p->setTopicBoxViewParameterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_getTopicWikipediaPageWikicode_putOnlyOneLineBreak() {
		$topicDescriptionWikicode = 'a';
		$topicBoxWikicode = 'b';
		$result = $this->p->getTopicWikipediaPageWikicode($topicDescriptionWikicode, $topicBoxWikicode);
		$this->assertSame("a\nb", $result);
	}
	
	function test_cleanTopicBoxTitleParameter_noApostrophes() {
		$topicBoxWikicode = '{{Featured topic box|title=No changes needed|column1=blah}}';
		$result = $this->p->cleanTopicBoxTitleParameter($topicBoxWikicode);
		$this->assertSame($topicBoxWikicode, $result);
	}
	
	function test_cleanTopicBoxTitleParameter_apostrophes() {
		$topicBoxWikicode = "{{Featured topic box|title=''Changes needed''|column1=blah}}";
		$result = $this->p->cleanTopicBoxTitleParameter($topicBoxWikicode);
		$this->assertSame('{{Featured topic box|title=Changes needed|column1=blah}}', $result);
	}
	
	function test_removeSignaturesFromTopicDescription_signature() {
		$topicDescriptionWikicode =
"<noinclude>'''''[[Meet the Woo 2]]''''' is the second mixtape by American rapper [[Pop Smoke]]. It was released on February 7, 2020, less than two weeks before the rapper was shot and killed at the age of 20 during a home invasion in Los Angeles. After many months of bringing all the articles to GA; it is finally ready. [[User:Shoot for the Stars|You know I'm shooting for the stars, aiming for the moon 💫]] ([[User talk:Shoot for the Stars|talk]]) 08:25, 26 May 2021 (UTC)</noinclude>";
		$result = $this->p->removeSignaturesFromTopicDescription($topicDescriptionWikicode);
		$this->assertSame(
"<noinclude>'''''[[Meet the Woo 2]]''''' is the second mixtape by American rapper [[Pop Smoke]]. It was released on February 7, 2020, less than two weeks before the rapper was shot and killed at the age of 20 during a home invasion in Los Angeles. After many months of bringing all the articles to GA; it is finally ready.</noinclude>"
		, $result);
	}
	
	function test_removeSignaturesFromTopicDescription_noSignature() {
		$topicDescriptionWikicode =
"<!---<noinclude>--->The [[EFL League One play-offs]] are a series of play-off matches contested by the association football teams finishing from third to sixth in [[EFL League One]], the third tier of English football, and are part of the [[English Football League play-offs]]. As of 2021, the play-offs comprise two semi-finals, where the team finishing third plays the team finishing sixth, and the team finishing fourth plays the team finishing fifth, each conducted as a two-legged tie. The winners of the semi-finals progress to the final which is contested at [[Wembley Stadium]].<!---</noinclude>--->";
		$result = $this->p->removeSignaturesFromTopicDescription($topicDescriptionWikicode);
		$this->assertSame($topicDescriptionWikicode, $result);
	}
	
	function test_removeTopicFromFGTC_middleOfPage() {
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1';
		$fgtcWikicode = 
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}';
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$result = $this->p->removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
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
		$result = $this->p->removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
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
		$result = $this->p->removeTopicFromFGTC($nominationPageTitle, $fgtcWikicode, $fgtcTitle);
		$this->assertSame(
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}'
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_normal() {
		$talkPageWikicode =
'{{Article history}}
{{Talk header}}

== Heading 1 ==
Test

== Heading 2 ==
Text';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'{{Article history}}
{{Talk header}}
[[Test]]

== Heading 1 ==
Test

== Heading 2 ==
Text'
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_ga1_1() {
		$talkPageWikicode =
'{{Article history}}
{{Talk header}}

{{Talk:abc/GA1}}

== Heading 1 ==
Test

== Heading 2 ==
Text';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'{{Article history}}
{{Talk header}}
[[Test]]

{{Talk:abc/GA1}}

== Heading 1 ==
Test

== Heading 2 ==
Text'
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_ga1_2() {
		$talkPageWikicode =
'{{Article history}}
{{Talk header}}

== Heading 1 ==
Test

{{Talk:abc/GA1}}

== Heading 2 ==
Text';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'{{Article history}}
{{Talk header}}
[[Test]]

== Heading 1 ==
Test

{{Talk:abc/GA1}}

== Heading 2 ==
Text'
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_blank() {
		$talkPageWikicode = '';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame('[[Test]]', $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_start() {
		$talkPageWikicode =
'== Heading 1 ==
Test';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'[[Test]]
== Heading 1 ==
Test'
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_end() {
		$talkPageWikicode = 'Test';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'Test
[[Test]]'
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_WikiProjectBannerShellPresent() {
		$talkPageWikicode =
'{{Test1}}
{{wikiproject banner shell}}
{{Test2}}

== Test3 ==';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'{{Test1}}
[[Test]]
{{wikiproject banner shell}}
{{Test2}}

== Test3 =='
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_WikiProjectPresent() {
		$talkPageWikicode =
'{{Test1}}
{{wikiproject tree of life}}
{{Test2}}

== Test3 ==';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'{{Test1}}
[[Test]]
{{wikiproject tree of life}}
{{Test2}}

== Test3 =='
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_deleteExtraNewLines() {
		$talkPageWikicode =
'{{GTC|Dua Lipa (album)|1}}
{{GA|06:30, 12 August 2020 (UTC)|topic=Music|page=1|oldid=972465209}}




{{Talk:Homesick (Dua Lipa song)/GA1}}

== this is a piano song ==';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'{{GTC|Dua Lipa (album)|1}}
{{GA|06:30, 12 August 2020 (UTC)|topic=Music|page=1|oldid=972465209}}
[[Test]]

{{Talk:Homesick (Dua Lipa song)/GA1}}

== this is a piano song =='
		, $result);
	}
	
	function test_addToTalkPageAboveWikiProjects_recognizeFootballTemplateAsWikiProject() {
		$talkPageWikicode = '{{football}}';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects($talkPageWikicode, $wikicodeToAdd);
		$this->assertSame(
'[[Test]]
{{football}}'
		, $result);
	}

/*
	test(`two wikiproject tempaltes detected`, () => {
		let talkPageWikicode = `{{wp banner shell}}{{football}}`;
		let wikicodeToAdd = `[[Test]]`;
		let output =
`[[Test]]
{{wp banner shell}}{{football}}`;
		expect(service.addToTalkPageAboveWikiProjects(talkPageWikicode, wikicodeToAdd)).toBe(output);
	});
*/
	
	function test_addArticleHistoryIfNotPresent_gaTemplateAtTopWithEnterUnderIt() {
		$talkPageWikicode =
'{{GA|05:06, 22 December 2020 (UTC)|topic=Sports and recreation|page=|oldid=995658831}}

{{WikiProject football|class=GA|importance=low|season=yes|england=yes}}';
		$talkPageTitle = 'Talk:2020 EFL League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
		$this->assertSame(
'{{Article history
|currentstatus = GA
|topic = Sports and recreation

|action1 = GAN
|action1date = 2020-12-22
|action1link = Talk:2020 EFL League Two play-off Final/GA1
|action1result = listed
|action1oldid = 995658831
}}
{{WikiProject football|class=GA|importance=low|season=yes|england=yes}}'
		, $result);
	}

	function test_addArticleHistoryIfNotPresent_gaTemplateWithBlankPage() {
		$talkPageWikicode = '{{GA|00:03, 5 January 2021 (UTC)|topic=Sports and recreation|page=|oldid=998352580}}';
		$talkPageTitle = 'Talk:History of Burnley F.C.';
		$result = $this->p->addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
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
	
	function test_addArticleHistoryIfNotPresent_gaTemplateWithNoPage() {
		$talkPageWikicode = '{{GA|14:05, 3 July 2021 (UTC)|topic=Sports and recreation|oldid=1031742022}}';
		$talkPageTitle = 'Talk:2007 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
		$this->assertSame(
'{{Article history
|currentstatus = GA
|topic = Sports and recreation

|action1 = GAN
|action1date = 2021-07-03
|action1link = Talk:2007 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1031742022
}}'
		, $result);
	}
	
	function test_addArticleHistoryIfNotPresent_gaSubtopic() {
		$talkPageWikicode = '{{GA|16:37, 31 January 2021 (UTC)|nominator=[[User:The Rambling Man|The Rambling Man]] <small>([[User talk:The Rambling Man|Stay alert! Control the virus! Save lives!&#33;!&#33;]])</small>|page=1|subtopic=Sports and recreation|note=|oldid=1003985565}}';
		$talkPageTitle = 'Talk:2014 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
		$this->assertSame(
'{{Article history
|currentstatus = GA
|topic = Sports and recreation

|action1 = GAN
|action1date = 2021-01-31
|action1link = Talk:2014 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1003985565
}}'
		, $result);
	}
	
	function test_addArticleHistoryIfNotPresent_gaNoTopic() {
		$talkPageWikicode = '{{GA|16:37, 31 January 2021 (UTC)|nominator=[[User:The Rambling Man|The Rambling Man]] <small>([[User talk:The Rambling Man|Stay alert! Control the virus! Save lives!&#33;!&#33;]])</small>|page=1|note=|oldid=1003985565}}';
		$talkPageTitle = 'Talk:2014 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent($talkPageWikicode, $talkPageTitle);
		$this->assertSame(
'{{Article history
|currentstatus = GA

|action1 = GAN
|action1date = 2021-01-31
|action1link = Talk:2014 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1003985565
}}'
		, $result);
	}

/*

	it(`should default to page=1 when no page parameter`, () => {
		let talkPageTitle =`Talk:Test`;
		let wikicode = `{{GA|20:19, 29 June 2022 (UTC)|topic=Language and literature}}`;
		let output =
`{{Article history
|currentstatus = GA
|topic = Language and literature

|action1 = GAN
|action1date = 20:19, 29 June 2022 (UTC)
|action1link = Talk:Test/GA1
|action1result = listed
}}`;
		expect(service.convertGATemplateToArticleHistoryIfPresent(talkPageTitle, wikicode)).toBe(output);
	});

	it(`should handle subtopic parameter`, () => {
		let talkPageTitle =`Talk:Test`;
		let wikicode = `{{GA|20:19, 29 June 2022 (UTC)|subtopic=Language and literature|page=1}}`;
		let output =
`{{Article history
|currentstatus = GA
|topic = Language and literature

|action1 = GAN
|action1date = 20:19, 29 June 2022 (UTC)
|action1link = Talk:Test/GA1
|action1result = listed
}}`;
		expect(service.convertGATemplateToArticleHistoryIfPresent(talkPageTitle, wikicode)).toBe(output);
	});

	it(`should handle oldid parameter`, () => {
		let talkPageTitle =`Talk:Test`;
		let wikicode = `{{GA|20:19, 29 June 2022 (UTC)|topic=Language and literature|page=1|oldid=123456789}}`;
		let output =
`{{Article history
|currentstatus = GA
|topic = Language and literature

|action1 = GAN
|action1date = 20:19, 29 June 2022 (UTC)
|action1link = Talk:Test/GA1
|action1result = listed
|action1oldid = 123456789
}}`;
		expect(service.convertGATemplateToArticleHistoryIfPresent(talkPageTitle, wikicode)).toBe(output);
	});

*/
	
	function test_getAllArticleTitles_normal() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize= 
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} [[2019 Tour Championship]] 
|column2=
:{{Icon|FA}} [[2020 Tour Championship]] 
|column3=
:{{Icon|GA}} [[2021 Tour Championship]] }}';
		$title = '';
		$result = $this->p->getAllArticleTitles($topicBoxWikicode, $title);
		$this->assertSame([
			'Tour Championship (snooker)',
			'2019 Tour Championship',
			'2020 Tour Championship',
			'2021 Tour Championship',
		], $result);
	}
	
	function test_getAllArticleTitles_extraSpaces() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize= 
|lead={{icon|GA}} [[Tour Championship (snooker) |Tour Championship]]
|column1=
:{{Icon|FA}} [[ 2019 Tour Championship]] 
|column2=
:{{Icon|FA}} [[  2020 Tour Championship  ]] 
|column3=
:{{Icon|GA}} [[2021 Tour Championship]] }}';
		$title = '';
		$result = $this->p->getAllArticleTitles($topicBoxWikicode, $title);
		$this->assertSame([
			'Tour Championship (snooker)',
			'2019 Tour Championship',
			'2020 Tour Championship',
			'2021 Tour Championship',
		], $result);
	}
	
	function test_getAllArticleTitles_ampersandPound32Semicolon() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize= 
|lead={{icon|GA}} [[French cruiser&#32;Sfax|French cruiser&nbsp;\'\'Sfax\'\']] }}';
		$title = '';
		$result = $this->p->getAllArticleTitles($topicBoxWikicode, $title);
		$this->assertSame([
			'French cruiser Sfax',
		], $result);
	}
	
	function test_getAllArticleTitles_noWikilink() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize= 
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} 2019 Tour Championship }}';
		$title = '';
		$this->expectException(GiveUpOnThisTopic::class);
		$this->p->getAllArticleTitles($topicBoxWikicode, $title);
	}
	
	function test_getAllArticleTitles_template() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize= 
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} {{2019 Tour Championship}} }}';
		$title = '';
		$this->expectException(GiveUpOnThisTopic::class);
		$this->p->getAllArticleTitles($topicBoxWikicode, $title);
	}
	
	function test_checkCounts_normal() {
		$goodArticleCount = 1;
		$featuredArticleCount = 1;
		$allArticleTitles = ['a', 'b'];
		$this->p->checkCounts($goodArticleCount, $featuredArticleCount, $allArticleTitles);
		$this->expectNotToPerformAssertions();
	}
	
	function test_checkCounts_incorrectSum() {
		$goodArticleCount = 1;
		$featuredArticleCount = 1;
		$allArticleTitles = ['a', 'b', 'c'];
		$this->expectException(GiveUpOnThisTopic::class);
		$this->p->checkCounts($goodArticleCount, $featuredArticleCount, $allArticleTitles);
	}
	
	function test_checkCounts_zero() {
		$goodArticleCount = 0;
		$featuredArticleCount = 0;
		$allArticleTitles = [];
		$this->expectException(GiveUpOnThisTopic::class);
		$this->p->checkCounts($goodArticleCount, $featuredArticleCount, $allArticleTitles);
	}
	
	function test_checkCounts_one() {
		$goodArticleCount = 1;
		$featuredArticleCount = 0;
		$allArticleTitles = ['a'];
		$this->expectException(GiveUpOnThisTopic::class);
		$this->p->checkCounts($goodArticleCount, $featuredArticleCount, $allArticleTitles);
	}
	
	function test_decideIfGoodOrFeatured_good() {
		$goodArticleCount = 2;
		$featuredArticleCount = 1;
		$result = $this->p->decideIfGoodOrFeatured($goodArticleCount, $featuredArticleCount);
		$this->assertSame('good', $result);
	}
	
	function test_decideIfGoodOrFeatured_featured() {
		$goodArticleCount = 1;
		$featuredArticleCount = 2;
		$result = $this->p->decideIfGoodOrFeatured($goodArticleCount, $featuredArticleCount);
		$this->assertSame('featured', $result);
	}
	
	function test_decideIfGoodOrFeatured_equal() {
		$goodArticleCount = 2;
		$featuredArticleCount = 2;
		$result = $this->p->decideIfGoodOrFeatured($goodArticleCount, $featuredArticleCount);
		$this->assertSame('featured', $result);
	}
	
	function test_decideIfGoodOrFeatured_zero() {
		$goodArticleCount = 0;
		$featuredArticleCount = 2;
		$result = $this->p->decideIfGoodOrFeatured($goodArticleCount, $featuredArticleCount);
		$this->assertSame('featured', $result);
	}
	
	function test_getGoodArticleCount() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize=
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} [[2019 Tour Championship]]
|column3=
:{{Icon|GA}} [[2021 Tour Championship]] }}';
		$result = $this->p->getGoodArticleCount($topicBoxWikicode);
		$this->assertSame(2, $result);
	}
	
	function test_getFeaturedArticleCount() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize=
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} [[2019 Tour Championship]]
|column2=
:{{Icon|FA}} [[2020 Tour Championship]] }}';
		$result = $this->p->getFeaturedArticleCount($topicBoxWikicode);
		$this->assertSame(2, $result);
	}
	
	function test_getFeaturedArticleCount_featuredList() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize=
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FL}} [[2019 Tour Championship]]
|column2=
:{{Icon|FA}} [[2020 Tour Championship]] }}';
		$result = $this->p->getFeaturedArticleCount($topicBoxWikicode);
		$this->assertSame(2, $result);
	}
	
	function test_addTopicToGoingsOn_noOtherTopicsPresent() {
		$goingsOnTitle = 'Wikipedia:Goings-on';
		$goingsOnWikicode =
"* [[:File:White-cheeked Honeyeater - Maddens Plains.jpg|White-cheeked honeyeater]] (1 Sep)

'''[[Wikipedia:Featured topics|Topics]] that gained featured status'''
|}
</div>

==See also==";
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Tour Championship (snooker)';
		$mainArticleTitle = 'Tour Championship (snooker)';
		$timestamp = 1630568338; // September 2, 2021, 07:38:58
		$result = $this->p->addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $mainArticleTitle, $timestamp);
		$this->assertSame(
"* [[:File:White-cheeked Honeyeater - Maddens Plains.jpg|White-cheeked honeyeater]] (1 Sep)

'''[[Wikipedia:Featured topics|Topics]] that gained featured status'''
* [[Wikipedia:Featured topics/Tour Championship (snooker)|Tour Championship (snooker)]] (2 Sep)
|}
</div>

==See also=="
		, $result);
	}
	
	function test_addTopicToGoingsOn_otherTopicsPresent_newestLast() {
		$goingsOnTitle = 'Wikipedia:Goings-on';
		$goingsOnWikicode =
"* [[:File:White-cheeked Honeyeater - Maddens Plains.jpg|White-cheeked honeyeater]] (1 Sep)

'''[[Wikipedia:Featured topics|Topics]] that gained featured status'''
* [[Wikipedia:Featured topics/Tour Championship (snooker) A|Tour Championship (snooker) A]] (1 Sep)
* [[Wikipedia:Featured topics/Tour Championship (snooker) B|Tour Championship (snooker) B]] (1 Sep)
|}
</div>

==See also==";
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Tour Championship (snooker)';
		$mainArticleTitle = 'Tour Championship (snooker)';
		$timestamp = 1630568338; // September 2, 2021, 07:38:58 UTC
		$result = $this->p->addTopicToGoingsOn($goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $mainArticleTitle, $timestamp);
		$this->assertSame(
"* [[:File:White-cheeked Honeyeater - Maddens Plains.jpg|White-cheeked honeyeater]] (1 Sep)

'''[[Wikipedia:Featured topics|Topics]] that gained featured status'''
* [[Wikipedia:Featured topics/Tour Championship (snooker) A|Tour Championship (snooker) A]] (1 Sep)
* [[Wikipedia:Featured topics/Tour Championship (snooker) B|Tour Championship (snooker) B]] (1 Sep)
* [[Wikipedia:Featured topics/Tour Championship (snooker)|Tour Championship (snooker)]] (2 Sep)
|}
</div>

==See also=="
		, $result);
	}
	
	function test_getNonMainArticleTitles() {
		$allArticleTitles = ['a', 'b', 'c'];
		$mainArticleTitle = 'b';
		$result = $this->p->getNonMainArticleTitles($allArticleTitles, $mainArticleTitle);
		$this->assertSame(['a', 'c'], $result);
	}
	
	function test_getWikiProjectBanners_dontAddBannerShellTwice() {
		$title = 'Wikipedia talk:Featured topics/Meet the Woo 2';
		$mainArticleTalkPageWikicode =
'{{WikiProject banner shell|1=
{{WikiProject Albums|class=GA|importance=low}}
{{WikiProject Hip hop|class=GA|importance=low}}
}}';
		$result = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame(
'{{WikiProject banner shell|1=
{{WikiProject Albums}}
{{WikiProject Hip hop}}
}}'
		, $result);
	}
		
	function test_getWikiProjectBanners_noParameters() {
		$title = '';
		$mainArticleTalkPageWikicode = '{{WikiProject Snooker}}';
		$result = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame('{{WikiProject Snooker}}', $result);
	}
	
	function test_getWikiProjectBanners_runTrimOnTemplateName() {
		$title = '';
		$mainArticleTalkPageWikicode = '{{WikiProject Snooker }}';
		$result = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame('{{WikiProject Snooker}}', $result);
	}
	
	function test_getWikiProjectBanners_parametersShouldBeRemoved() {
		$title = '';
		$mainArticleTalkPageWikicode = '{{WikiProject Snooker |class=GA|importance=Low}}';
		$result = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame('{{WikiProject Snooker}}', $result);
	}
	
	function test_getWikiProjectBanners_threeBannersShouldGetBannerShell() {
		$title = '';
		$mainArticleTalkPageWikicode =
'{{WikiProject Cue Sports}}
{{WikiProject Biography}}
{{WikiProject Women}}';
		$result = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame(
'{{WikiProject banner shell|1=
{{WikiProject Cue Sports}}
{{WikiProject Biography}}
{{WikiProject Women}}
}}'
		, $result);
	}
	
	function test_getWikiProjectBanners_twoBannersShouldGetBannerShell() {
		$title = '';
		$mainArticleTalkPageWikicode =
'{{WikiProject Cue Sports}}
{{WikiProject Biography}}';
		$result = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame(
'{{WikiProject banner shell|1=
{{WikiProject Cue Sports}}
{{WikiProject Biography}}
}}'
		, $result);
	}
	
	function test_getWikiProjectBanners_oneBannerShouldNotGetBannerShell() {
		$title = '';
		$mainArticleTalkPageWikicode = '{{WikiProject Cue Sports}}';
		$result = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame('{{WikiProject Cue Sports}}', $result);
	}
	
	function test_getWikiProjectBanners_doNotDetectShellAsWikiProjects() {
		$title = '';
		$mainArticleTalkPageWikicode =
'List generated from https://en.wikipedia.org/w/index.php?title=Special:WhatLinksHere/Template:WikiProject_banner_shell&hidetrans=1&hidelinks=1

{{WikiProjectBanners}}
{{WikiProject Banners}}
{{WPB}}
{{WPBS}}
{{Wikiprojectbannershell}}
{{WikiProject Banner Shell}}
{{Wpb}}
{{WPBannerShell}}
{{Wpbs}}
{{Wikiprojectbanners}}
{{WP Banner Shell}}
{{WP banner shell}}
{{Bannershell}}
{{Wikiproject banner shell}}
{{WikiProject Banners Shell}}
{{WikiProjectBanner Shell}}
{{WikiProjectBannerShell}}
{{WikiProject BannerShell}}
{{WikiprojectBannerShell}}
{{WikiProject banner shell/redirect}}
{{WikiProject Shell}}
{{Scope shell}}
{{Project shell}}
{{WikiProject shell}}
{{WikiProject banner}}
{{Wpbannershell}}
{{Multiple wikiprojects}}

Only this one should be detected:
{{WikiProject Cue Sports}}';
		$result = $this->p->getWikiProjectBanners($mainArticleTalkPageWikicode, $title);
		$this->assertSame('{{WikiProject Cue Sports}}', $result);
	}
	
	function test_getTopicTalkPageTitle() {
		$mainArticleTitle = 'Dua Lipa (album)';
		$result = $this->p->getTopicTalkPageTitle($mainArticleTitle);
		$this->assertSame('Wikipedia talk:Featured topics/Dua Lipa (album)', $result);
	}
	
	function test_setTopicBoxTitleParameter_noTitle() {
		$topicBoxWikicode = '{{Featured topic box}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParameter($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame(
'{{Featured topic box
|title=Test article
}}'
		, $result);
	}
	
	function test_setTopicBoxTitleParameter_blankTitle() {
		$topicBoxWikicode = '{{Featured topic box|title=}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParameter($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('{{Featured topic box|title=Test article}}', $result);
	}
	
	function test_setTopicBoxTitleParameter_alreadyHasTitle() {
		$topicBoxWikicode = '{{Featured topic box|title=Test article}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParameter($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('{{Featured topic box|title=Test article}}', $result);
	}
	
	function test_getTopicDescriptionWikicode_simple() {
		$callerPageWikicode = 
'===Protected cruisers of France===
In the 1880s and 1890s, the [[French Navy]] built a series of [[protected cruiser]]s, some 33 ships in total. The ships filled a variety of roles, and their varying designs represented the strategic and doctrinal conflicts in the French naval command at that time. The factions included those who favored a strong main fleet in French waters, those who preferred the long-range commerce raiders prescribed by the [[Jeune Ecole]], and those who wanted a fleet based on colonial requirements. Eventually, the type was superseded in French service by more powerful [[armored cruiser]]s.

{{Featured topic box}}';
		$result = $this->p->getTopicDescriptionWikicode($callerPageWikicode);
		$this->assertSame(
'<noinclude>In the 1880s and 1890s, the [[French Navy]] built a series of [[protected cruiser]]s, some 33 ships in total. The ships filled a variety of roles, and their varying designs represented the strategic and doctrinal conflicts in the French naval command at that time. The factions included those who favored a strong main fleet in French waters, those who preferred the long-range commerce raiders prescribed by the [[Jeune Ecole]], and those who wanted a fleet based on colonial requirements. Eventually, the type was superseded in French service by more powerful [[armored cruiser]]s.</noinclude>'
		, $result);
	}
	
	function test_getTopicDescriptionWikicode_hasTemplateInDescription() {
		$callerPageWikicode = 
'===Protected cruisers of France===
In the 1880s and 1890s, the [[French Navy]] built a series of [[protected cruiser]]s, some 33 ships in total. The ships filled a variety of roles, and their varying designs represented the strategic and doctrinal conflicts in the French naval command at that time. The factions included those who favored a strong main fleet in French waters, those who preferred the long-range commerce raiders prescribed by the {{lang|fr|[[Jeune Ecole]]}}, and those who wanted a fleet based on colonial requirements. Eventually, the type was superseded in French service by more powerful [[armored cruiser]]s.

{{Featured topic box}}';
		$result = $this->p->getTopicDescriptionWikicode($callerPageWikicode);
		$this->assertSame(
'<noinclude>In the 1880s and 1890s, the [[French Navy]] built a series of [[protected cruiser]]s, some 33 ships in total. The ships filled a variety of roles, and their varying designs represented the strategic and doctrinal conflicts in the French naval command at that time. The factions included those who favored a strong main fleet in French waters, those who preferred the long-range commerce raiders prescribed by the {{lang|fr|[[Jeune Ecole]]}}, and those who wanted a fleet based on colonial requirements. Eventually, the type was superseded in French service by more powerful [[armored cruiser]]s.</noinclude>'
		, $result);
	}
	
	function test_getTopicDescriptionWikicode_commentedNoInclude() {
		$callerPageWikicode = 
'
===[[Wikipedia:Featured and good topic candidates/EFL League Two play-offs/archive1|EFL League Two play-offs]]===
<!---<noinclude>--->The [[EFL League Two play-offs]] are a series of play-off matches contested by the association football teams finishing from fourth to seventh in [[EFL League Two]], the fourth tier of English football, and are part of the [[English Football League play-offs]]. As of 2021, the play-offs comprise two semi-finals, where the team finishing third plays the team finishing sixth, and the team finishing fourth plays the team finishing fifth, each conducted as a two-legged tie. The winners of the semi-finals progress to the final which is contested at [[Wembley Stadium]].<!---</noinclude>--->

{{Featured topic box}}';
		$result = $this->p->getTopicDescriptionWikicode($callerPageWikicode);
		$this->assertSame(
'<noinclude>The [[EFL League Two play-offs]] are a series of play-off matches contested by the association football teams finishing from fourth to seventh in [[EFL League Two]], the fourth tier of English football, and are part of the [[English Football League play-offs]]. As of 2021, the play-offs comprise two semi-finals, where the team finishing third plays the team finishing sixth, and the team finishing fourth plays the team finishing fifth, each conducted as a two-legged tie. The winners of the semi-finals progress to the final which is contested at [[Wembley Stadium]].</noinclude>'
		, $result);
	}
	
	function test_getTopicTitle_withTopic() {
		$topicBoxWikicode =
'{{Featured topic box |title=UEFA European Championship finals |count=17 |image=Coupe Henri Delaunay 2017.jpg |imagesize= 
|lead={{icon|FL}} [[List of UEFA European Championship finals|UEFA European Championship finals]]
|column1=
:{{icon|GA}} [[UEFA Euro 2020 Final]] }}';
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('UEFA European Championship finals', $result);
	}
	
	function test_getTopicTitle_withTopic_bold() {
		$topicBoxWikicode = "{{Featured topic box |title='''UEFA European Championship finals''' |count=17}}";
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('UEFA European Championship finals', $result);
	}
	
	function test_getTopicTitle_withTopic_boldItalic() {
		$topicBoxWikicode = "{{Featured topic box |title='''''UEFA European Championship finals''''' |count=17}}";
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('UEFA European Championship finals', $result);
	}
	
	function test_getTopicTitle_withTopic_italic() {
		$topicBoxWikicode = "{{Featured topic box |title=''UEFA European Championship finals'' |count=17}}";
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('UEFA European Championship finals', $result);
	}
	
	function test_getTopicTitle_noTopic() {
		$topicBoxWikicode =
'{{Featured topic box |count=17 |image=Coupe Henri Delaunay 2017.jpg |imagesize= 
|lead={{icon|FL}} [[List of UEFA European Championship finals|UEFA European Championship finals]]
|column1=
:{{icon|GA}} [[UEFA Euro 2020 Final]] }}';
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('List of UEFA European Championship finals', $result);
	}

	function test_abortIfPromotionTemplateMissing_promoteDoneNo() {
		$wikicode = '{{User:NovemBot/Promote}}';
		$title = 'Wikipedia:Featured and good topic candidates/NASA Astronaut Group 2/archive1';
		$this->p->abortIfPromotionTemplateMissing($wikicode, $title);
		$this->expectNotToPerformAssertions();
	}

	function test_abortIfPromotionTemplateMissing_promoteDoneYes() {
		$wikicode = '{{User:NovemBot/Promote|done=yes}}';
		$title = 'Wikipedia:Featured and good topic candidates/NASA Astronaut Group 2/archive1';
		$this->expectException(GiveUpOnThisTopic::class);
		$this->p->abortIfPromotionTemplateMissing($wikicode, $title);
	}

	function test_abortIfPromotionTemplateMissing_noTemplate() {
		$wikicode = 'Test';
		$title = 'Wikipedia:Featured and good topic candidates/NASA Astronaut Group 2/archive1';
		$this->expectException(GiveUpOnThisTopic::class);
		$this->p->abortIfPromotionTemplateMissing($wikicode, $title);
	}

	function test_getMainArticleTitle_notPiped() {
		$title = 'Wikipedia:Featured topics/Billboard number-one country songs';
		$topicBoxWikicode =
"{{Featured topic box |title=Billboard number-one country songs |count=78 |image=Country music legends.jpg |imagesize=200 
|lead={{icon|FL}} [[List of Billboard number-one country songs]] }}";
		$result = $this->p->getMainArticleTitle($topicBoxWikicode, $title);
		$this->assertSame("List of Billboard number-one country songs", $result);
	}

	function test_getMainArticleTitle_piped() {
		$title = 'Wikipedia:Featured topics/Billboard number-one country songs';
		$topicBoxWikicode =
"{{Featured topic box |title=Billboard number-one country songs |count=78 |image=Country music legends.jpg |imagesize=200 
|lead={{icon|FL}} [[List of Billboard number-one country songs|''Billboard'' number-one country songs]] }}";
		$result = $this->p->getMainArticleTitle($topicBoxWikicode, $title);
		$this->assertSame("List of Billboard number-one country songs", $result);
	}

	function test_getMainArticleTitle_spaceAtEnd() {
		$title = 'Wikipedia:Featured topics/Billboard number-one country songs';
		$topicBoxWikicode =
"{{Featured topic box |title=Billboard number-one country songs |count=78 |image=Country music legends.jpg |imagesize=200 
|lead={{icon|FL}} [[List of Billboard number-one country songs |''Billboard'' number-one country songs]] }}";
		$result = $this->p->getMainArticleTitle($topicBoxWikicode, $title);
		$this->assertSame("List of Billboard number-one country songs", $result);
	}
}