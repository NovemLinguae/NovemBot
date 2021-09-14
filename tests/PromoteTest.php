<?php

use PHPUnit\Framework\TestCase;

class PromoteTest extends TestCase {
	function setUp(): void {
		// stub EchoHelper so that it doesn't echo
		$eh = $this->createStub(EchoHelper::class);
		
		$h = new Helper();
		$this->p = new Promote($eh, $h);
	}

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
	function test_addArticleHistoryIfNotPresent_gaTemplateWithNoPage() {
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
	
	function test_markDoneAndSuccessful_noIndent() {
		$nominationPageWikicode = 
"**No worries, I intend to take 2021 to FAC later this year. Best Wishes, '''[[User:Lee Vilenski|<span style=\"color:green\">Lee Vilenski</span>]] <sup>([[User talk:Lee Vilenski|talk]] • [[Special:Contribs/Lee Vilenski|contribs]])</sup>''' 12:59, 25 August 2021 (UTC)
{{  User:NovemBot/Promote  }} [[User:Aza24|Aza24]] ([[User talk:Aza24|talk]]) 21:27, 1 September 2021 (UTC)
:{{@FTC}} - hi, I'm not super familiar with FLC, is there anything further that I need to do with this nomination? Best Wishes, '''[[User:Lee Vilenski|<span style=\"color:green\">Lee Vilenski</span>]] <sup>([[User talk:Lee Vilenski|talk]] • [[Special:Contribs/Lee Vilenski|contribs]])</sup>''' 19:24, 31 August 2021 (UTC)";
		$nominationPageTitle = 'Sample page';
		$result = $this->p->markDoneAndSuccessful($nominationPageWikicode, $nominationPageTitle);
		$this->assertSame(
"**No worries, I intend to take 2021 to FAC later this year. Best Wishes, '''[[User:Lee Vilenski|<span style=\"color:green\">Lee Vilenski</span>]] <sup>([[User talk:Lee Vilenski|talk]] • [[Special:Contribs/Lee Vilenski|contribs]])</sup>''' 12:59, 25 August 2021 (UTC)
{{  User:NovemBot/Promote  |done=yes}} [[User:Aza24|Aza24]] ([[User talk:Aza24|talk]]) 21:27, 1 September 2021 (UTC)
:Promotion completed successfully. ~~~~
:{{@FTC}} - hi, I'm not super familiar with FLC, is there anything further that I need to do with this nomination? Best Wishes, '''[[User:Lee Vilenski|<span style=\"color:green\">Lee Vilenski</span>]] <sup>([[User talk:Lee Vilenski|talk]] • [[Special:Contribs/Lee Vilenski|contribs]])</sup>''' 19:24, 31 August 2021 (UTC)"
		, $result);
	}
	
	function test_markDoneAndSuccessful_oneBullet() {
		$nominationPageWikicode = 
"*{{User:NovemBot/Promote}} [[User:Aza24|Aza24]] ([[User talk:Aza24|talk]]) 03:35, 13 September 2021 (UTC)";
		$nominationPageTitle = 'Sample page';
		$result = $this->p->markDoneAndSuccessful($nominationPageWikicode, $nominationPageTitle);
		$this->assertSame(
"*{{User:NovemBot/Promote|done=yes}} [[User:Aza24|Aza24]] ([[User talk:Aza24|talk]]) 03:35, 13 September 2021 (UTC)
**Promotion completed successfully. ~~~~"
		, $result);
	}
	
	function test_markDoneAndSuccessful_spacesNearIndent() {
		$nominationPageWikicode = 
"* {{User:NovemBot/Promote}} [[User:Aza24|Aza24]] ([[User talk:Aza24|talk]]) 03:35, 13 September 2021 (UTC)";
		$nominationPageTitle = 'Sample page';
		$result = $this->p->markDoneAndSuccessful($nominationPageWikicode, $nominationPageTitle);
		$this->assertSame(
"* {{User:NovemBot/Promote|done=yes}} [[User:Aza24|Aza24]] ([[User talk:Aza24|talk]]) 03:35, 13 September 2021 (UTC)
**Promotion completed successfully. ~~~~"
		, $result);
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
	
	function test_setTopicBoxTitleParamter_noTitle() {
		$topicBoxWikicode = '{{Featured topic box}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParamter($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame(
'{{Featured topic box
|title=Test article
}}'
		, $result);
	}
	
	function test_setTopicBoxTitleParamter_blankTitle() {
		$topicBoxWikicode = '{{Featured topic box|title=}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParamter($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('{{Featured topic box|title=Test article}}', $result);
	}
	
	function test_setTopicBoxTitleParamter_alreadyHasTitle() {
		$topicBoxWikicode = '{{Featured topic box|title=Test article}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParamter($topicBoxWikicode, $mainArticleTitle);
		$this->assertSame('{{Featured topic box|title=Test article}}', $result);
	}
}