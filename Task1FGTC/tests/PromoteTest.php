<?php

use PHPUnit\Framework\TestCase;

/**
 * @todo add @group for grouping. this is equivlent to Jest's "describe"
 */
class PromoteTest extends TestCase {
	protected $p;

	public function setUp(): void {
		// stub EchoHelper so that it doesn't echo
		$eh = $this->createStub( EchoHelper::class );

		$h = new Helper();
		$this->p = new Promote( $eh, $h );
	}

	public function test_getTopicWikipediaPageTitle_dontWriteToWikipediaGoodTopics() {
		$mainArticleTitle = 'TestPage';
		$goodOrFeatured = 'good';
		$result = $this->p->getTopicWikipediaPageTitle( $mainArticleTitle, $goodOrFeatured );
		$this->assertSame( 'Wikipedia:Featured topics/TestPage', $result );
	}

	public function test_setTopicBoxViewParameterToYes_inputContainsViewYes() {
		$topicBoxWikicode = '{{Featured topic box|view=yes}}';
		$result = $this->p->setTopicBoxViewParameterToYes( $topicBoxWikicode );
		$this->assertSame( '{{Featured topic box|view=yes}}', $result );
	}

	public function test_setTopicBoxViewParameterToYes_inputContainsViewYes2() {
		$topicBoxWikicode = '{{Featured topic box | view = yes }}';
		$result = $this->p->setTopicBoxViewParameterToYes( $topicBoxWikicode );
		$this->assertSame( '{{Featured topic box | view = yes }}', $result );
	}

	public function test_setTopicBoxViewParameterToYes_inputContainsViewYes3() {
		$topicBoxWikicode =
'{{Featured topic box
| view = yes
}}';
		$result = $this->p->setTopicBoxViewParameterToYes( $topicBoxWikicode );
		$this->assertSame(
'{{Featured topic box
| view = yes
}}', $result );
	}

	public function test_setTopicBoxViewParameterToYes_inputContainsViewNo1() {
		$topicBoxWikicode = '{{Featured topic box|view=no}}';
		$result = $this->p->setTopicBoxViewParameterToYes( $topicBoxWikicode );
		$this->assertSame(
'{{Featured topic box
|view=yes
}}', $result );
	}

	public function test_setTopicBoxViewParameterToYes_inputContainsViewNo2() {
		$topicBoxWikicode =
'{{Featured topic box
| view = no
}}';
		$result = $this->p->setTopicBoxViewParameterToYes( $topicBoxWikicode );
		$this->assertSame(
'{{Featured topic box
|view=yes
}}', $result );
	}

	public function test_setTopicBoxViewParameterToYes_inputIsJustTemplateName1() {
		$topicBoxWikicode = '{{Featured topic box}}';
		$result = $this->p->setTopicBoxViewParameterToYes( $topicBoxWikicode );
		$this->assertSame(
'{{Featured topic box
|view=yes
}}', $result );
	}

	public function test_setTopicBoxViewParameterToYes_inputIsJustTemplateName2() {
		$topicBoxWikicode =
'{{Featured topic box

}}';
		$result = $this->p->setTopicBoxViewParameterToYes( $topicBoxWikicode );
		$this->assertSame(
'{{Featured topic box
|view=yes
}}', $result );
	}

	public function test_getTopicWikipediaPageWikicode_putOnlyOneLineBreak() {
		$topicDescriptionWikicode = 'a';
		$topicBoxWikicode = 'b';
		$result = $this->p->getTopicWikipediaPageWikicode( $topicDescriptionWikicode, $topicBoxWikicode );
		$this->assertSame( "a\nb", $result );
	}

	public function test_cleanTopicBoxTitleParameter_noApostrophes() {
		$topicBoxWikicode = '{{Featured topic box|title=No changes needed|column1=blah}}';
		$result = $this->p->cleanTopicBoxTitleParameter( $topicBoxWikicode );
		$this->assertSame( $topicBoxWikicode, $result );
	}

	public function test_cleanTopicBoxTitleParameter_apostrophes() {
		$topicBoxWikicode = "{{Featured topic box|title=''Changes needed''|column1=blah}}";
		$result = $this->p->cleanTopicBoxTitleParameter( $topicBoxWikicode );
		$this->assertSame( '{{Featured topic box|title=Changes needed|column1=blah}}', $result );
	}

	public function test_removeSignaturesFromTopicDescription_signature() {
		$topicDescriptionWikicode =
"<noinclude>'''''[[Meet the Woo 2]]''''' is the second mixtape by American rapper [[Pop Smoke]]. It was released on February 7, 2020, less than two weeks before the rapper was shot and killed at the age of 20 during a home invasion in Los Angeles. After many months of bringing all the articles to GA; it is finally ready. [[User:Shoot for the Stars|You know I'm shooting for the stars, aiming for the moon 💫]] ([[User talk:Shoot for the Stars|talk]]) 08:25, 26 May 2021 (UTC)</noinclude>";
		$result = $this->p->removeSignaturesFromTopicDescription( $topicDescriptionWikicode );
		$this->assertSame(
"<noinclude>'''''[[Meet the Woo 2]]''''' is the second mixtape by American rapper [[Pop Smoke]]. It was released on February 7, 2020, less than two weeks before the rapper was shot and killed at the age of 20 during a home invasion in Los Angeles. After many months of bringing all the articles to GA; it is finally ready.</noinclude>", $result );
	}

	public function test_removeSignaturesFromTopicDescription_noSignature() {
		$topicDescriptionWikicode =
"<!---<noinclude>--->The [[EFL League One play-offs]] are a series of play-off matches contested by the association football teams finishing from third to sixth in [[EFL League One]], the third tier of English football, and are part of the [[English Football League play-offs]]. As of 2021, the play-offs comprise two semi-finals, where the team finishing third plays the team finishing sixth, and the team finishing fourth plays the team finishing fifth, each conducted as a two-legged tie. The winners of the semi-finals progress to the final which is contested at [[Wembley Stadium]].<!---</noinclude>--->";
		$result = $this->p->removeSignaturesFromTopicDescription( $topicDescriptionWikicode );
		$this->assertSame( $topicDescriptionWikicode, $result );
	}

	public function test_removeTopicFromFGTC_middleOfPage() {
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1';
		$fgtcWikicode =
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}';
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$result = $this->p->removeTopicFromFGTC( $nominationPageTitle, $fgtcWikicode, $fgtcTitle );
		$this->assertSame(
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}', $result );
	}

	public function test_removeTopicFromFGTC_lastLineOnPage() {
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1';
		$fgtcWikicode =
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}
{{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}}';
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$result = $this->p->removeTopicFromFGTC( $nominationPageTitle, $fgtcWikicode, $fgtcTitle );
		$this->assertSame(
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}', $result );
	}

	public function test_removeTopicFromFGTC_firstLineOnPage() {
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1';
		$fgtcWikicode =
'{{Wikipedia:Featured and good topic candidates/Meet the Woo 2/archive1}}
{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}';
		$fgtcTitle = 'Wikipedia:Featured and good topic candidates';
		$result = $this->p->removeTopicFromFGTC( $nominationPageTitle, $fgtcWikicode, $fgtcTitle );
		$this->assertSame(
'{{Wikipedia:Featured and good topic candidates/Protected cruisers of France/archive1}}
{{Wikipedia:Featured and good topic candidates/EFL League One play-offs/archive1}}', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_normal() {
		$talkPageWikicode =
'{{Article history}}
{{Talk header}}

== Heading 1 ==
Test

== Heading 2 ==
Text';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'{{Article history}}
{{Talk header}}
[[Test]]

== Heading 1 ==
Test

== Heading 2 ==
Text', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_ga1_1() {
		$talkPageWikicode =
'{{Article history}}
{{Talk header}}

{{Talk:abc/GA1}}

== Heading 1 ==
Test

== Heading 2 ==
Text';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'{{Article history}}
{{Talk header}}
[[Test]]

{{Talk:abc/GA1}}

== Heading 1 ==
Test

== Heading 2 ==
Text', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_ga1_2() {
		$talkPageWikicode =
'{{Article history}}
{{Talk header}}

== Heading 1 ==
Test

{{Talk:abc/GA1}}

== Heading 2 ==
Text';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'{{Article history}}
{{Talk header}}
[[Test]]

== Heading 1 ==
Test

{{Talk:abc/GA1}}

== Heading 2 ==
Text', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_blank() {
		$talkPageWikicode = '';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame( '[[Test]]', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_start() {
		$talkPageWikicode =
'== Heading 1 ==
Test';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'[[Test]]
== Heading 1 ==
Test', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_end() {
		$talkPageWikicode = 'Test';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'Test
[[Test]]', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_WikiProjectBannerShellPresent() {
		$talkPageWikicode =
'{{Test1}}
{{wikiproject banner shell}}
{{Test2}}

== Test3 ==';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'{{Test1}}
[[Test]]
{{wikiproject banner shell}}
{{Test2}}

== Test3 ==', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_WikiProjectPresent() {
		$talkPageWikicode =
'{{Test1}}
{{wikiproject tree of life}}
{{Test2}}

== Test3 ==';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'{{Test1}}
[[Test]]
{{wikiproject tree of life}}
{{Test2}}

== Test3 ==', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_deleteExtraNewLines() {
		$talkPageWikicode =
'{{GTC|Dua Lipa (album)|1}}
{{GA|06:30, 12 August 2020 (UTC)|topic=Music|page=1|oldid=972465209}}

{{Talk:Homesick (Dua Lipa song)/GA1}}

== this is a piano song ==';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'{{GTC|Dua Lipa (album)|1}}
{{GA|06:30, 12 August 2020 (UTC)|topic=Music|page=1|oldid=972465209}}
[[Test]]

{{Talk:Homesick (Dua Lipa song)/GA1}}

== this is a piano song ==', $result );
	}

	public function test_addToTalkPageAboveWikiProjects_recognizeFootballTemplateAsWikiProject() {
		$talkPageWikicode = '{{football}}';
		$wikicodeToAdd = '[[Test]]';
		$result = $this->p->addToTalkPageAboveWikiProjects( $talkPageWikicode, $wikicodeToAdd );
		$this->assertSame(
'[[Test]]
{{football}}', $result );
	}

	public function test_addArticleHistoryIfNotPresent_gaTemplateAtTopWithEnterUnderIt() {
		$talkPageWikicode =
'{{GA|05:06, 22 December 2020 (UTC)|topic=Sports and recreation|page=|oldid=995658831}}

{{WikiProject football|class=GA|importance=low|season=yes|england=yes}}';
		$talkPageTitle = 'Talk:2020 EFL League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle );
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

{{WikiProject football|class=GA|importance=low|season=yes|england=yes}}', $result );
	}

	public function test_addArticleHistoryIfNotPresent_gaTemplateWithBlankPage() {
		$talkPageWikicode = '{{GA|00:03, 5 January 2021 (UTC)|topic=Sports and recreation|page=|oldid=998352580}}';
		$talkPageTitle = 'Talk:History of Burnley F.C.';
		$result = $this->p->addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle );
		$this->assertSame(
'{{Article history
|currentstatus = GA
|topic = Sports and recreation

|action1 = GAN
|action1date = 2021-01-05
|action1link = Talk:History of Burnley F.C./GA1
|action1result = listed
|action1oldid = 998352580
}}
', $result );
	}

	public function test_addArticleHistoryIfNotPresent_gaTemplateWithNoPage() {
		$talkPageWikicode = '{{GA|14:05, 3 July 2021 (UTC)|topic=Sports and recreation|oldid=1031742022}}';
		$talkPageTitle = 'Talk:2007 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle );
		$this->assertSame(
'{{Article history
|currentstatus = GA
|topic = Sports and recreation

|action1 = GAN
|action1date = 2021-07-03
|action1link = Talk:2007 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1031742022
}}
', $result );
	}

	public function test_addArticleHistoryIfNotPresent_gaSubtopic() {
		$talkPageWikicode = '{{GA|16:37, 31 January 2021 (UTC)|nominator=[[User:The Rambling Man|The Rambling Man]] <small>([[User talk:The Rambling Man|Stay alert! Control the virus! Save lives!&#33;!&#33;]])</small>|page=1|subtopic=Sports and recreation|note=|oldid=1003985565}}';
		$talkPageTitle = 'Talk:2014 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle );
		$this->assertSame(
'{{Article history
|currentstatus = GA
|topic = Sports and recreation

|action1 = GAN
|action1date = 2021-01-31
|action1link = Talk:2014 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1003985565
}}
', $result );
	}

	public function test_addArticleHistoryIfNotPresent_gaNoTopic() {
		$talkPageWikicode = '{{GA|16:37, 31 January 2021 (UTC)|nominator=[[User:The Rambling Man|The Rambling Man]] <small>([[User talk:The Rambling Man|Stay alert! Control the virus! Save lives!&#33;!&#33;]])</small>|page=1|note=|oldid=1003985565}}';
		$talkPageTitle = 'Talk:2014 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle );
		$this->assertSame(
'{{Article history
|currentstatus = GA

|action1 = GAN
|action1date = 2021-01-31
|action1link = Talk:2014 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1003985565
}}
', $result );
	}

	public function test_addArticleHistoryIfNotPresent_dontDeleteSimilarTemplateGAList() {
		$talkPageWikicode =
'{{GA|16:37, 31 January 2021 (UTC)|nominator=[[User:The Rambling Man|The Rambling Man]] <small>([[User talk:The Rambling Man|Stay alert! Control the virus! Save lives!&#33;!&#33;]])</small>|page=1|note=|oldid=1003985565}}

== Test ==
{{GAList/check|aye}}
';
		$talkPageTitle = 'Talk:2014 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle );
		$this->assertSame(
'{{Article history
|currentstatus = GA

|action1 = GAN
|action1date = 2021-01-31
|action1link = Talk:2014 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1003985565
}}

== Test ==
{{GAList/check|aye}}', $result );
	}

	public function test_addArticleHistoryIfNotPresent_detectArticleHistoryTemplateWithNoSpace() {
		$talkPageWikicode =
'{{ArticleHistory
|currentstatus = GA

|action1 = GAN
|action1date = 2021-01-31
|action1link = Talk:2014 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1003985565
}} {{GAList/check|aye}}';
		$talkPageTitle = 'Talk:2014 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle );
		$this->assertSame(
'{{ArticleHistory
|currentstatus = GA

|action1 = GAN
|action1date = 2021-01-31
|action1link = Talk:2014 Football League Two play-off Final/GA1
|action1result = listed
|action1oldid = 1003985565
}} {{GAList/check|aye}}', $result );
	}

	public function test_addArticleHistoryIfNotPresent_gaTemplateWithNoOldId() {
		$talkPageWikicode = '{{GA|16:37, 31 January 2021 (UTC)|nominator=[[User:The Rambling Man|The Rambling Man]] <small>([[User talk:The Rambling Man|Stay alert! Control the virus! Save lives!&#33;!&#33;]])</small>|page=1|note=}}';
		$talkPageTitle = 'Talk:2014 Football League Two play-off Final';
		$result = $this->p->addArticleHistoryIfNotPresent( $talkPageWikicode, $talkPageTitle );
		$this->assertSame(
'{{Article history
|currentstatus = GA

|action1 = GAN
|action1date = 2021-01-31
|action1link = Talk:2014 Football League Two play-off Final/GA1
|action1result = listed
}}
', $result );
	}

	public function test_getAllArticleTitles_normal() {
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
		$result = $this->p->getAllArticleTitles( $topicBoxWikicode, $title );
		$this->assertSame( [
			'Tour Championship (snooker)',
			'2019 Tour Championship',
			'2020 Tour Championship',
			'2021 Tour Championship',
		], $result );
	}

	public function test_getAllArticleTitles_extraSpaces() {
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
		$result = $this->p->getAllArticleTitles( $topicBoxWikicode, $title );
		$this->assertSame( [
			'Tour Championship (snooker)',
			'2019 Tour Championship',
			'2020 Tour Championship',
			'2021 Tour Championship',
		], $result );
	}

	public function test_getAllArticleTitles_ampersandPound32Semicolon() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize= 
|lead={{icon|GA}} [[French cruiser&#32;Sfax|French cruiser&nbsp;\'\'Sfax\'\']] }}';
		$title = '';
		$result = $this->p->getAllArticleTitles( $topicBoxWikicode, $title );
		$this->assertSame( [
			'French cruiser Sfax',
		], $result );
	}

	public function test_getAllArticleTitles_noWikilink() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize= 
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} 2019 Tour Championship }}';
		$title = '';
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->getAllArticleTitles( $topicBoxWikicode, $title );
	}

	public function test_getAllArticleTitles_template() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize= 
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} {{2019 Tour Championship}} }}';
		$title = '';
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->getAllArticleTitles( $topicBoxWikicode, $title );
	}

	public function test_getAllArticleTitles_pipedWikilink() {
		$topicBoxWikicode =
"{{Featured topic box |title=Blonde on Blonde |count=15 |image=Bob-Dylan-arrived-at-Arlanda-surrounded-by-twenty-bodyguards-and-assistants-391770740297 (cropped).jpg |imagesize= 80
|lead={{classicon|FA}} ''[[Blonde on Blonde]]''
|column1=
:{{icon|GA}} \"[[Rainy Day Women ♯12 & 35|Rainy Day Women #12 & 35]]\"
}}";
		$title = 'Blonde on Blonde';
		$expected = [
			'Blonde on Blonde',
			'Rainy Day Women ♯12 & 35',
		];
		$result = $this->p->getAllArticleTitles( $topicBoxWikicode, $title );
		$this->assertSame( $expected, $result );
	}

	public function test_getAllArticleTitles_missingLineBreaks() {
		$topicBoxWikicode =
"{{Featured topic box|title=Hypericum sect. Androsaemum|lead={{icon|GA}} [[Hypericum sect. Androsaemum|''Hypericum'' sect. ''Androsaemum'']]|view=|count=6|image=Hypericum inodorum 'Golden Beacon' J1.jpg|imagesize=100|column1=: {{icon|GA}} ''[[Hypericum androsaemum]]''
: {{icon|GA}} ''[[Hypericum hircinum]]''|column2=: {{icon|GA}} ''[[Hypericum foliosum]]''
: {{icon|GA}} ''[[Hypericum × inodorum]]''|column3=: {{icon|GA}} ''[[Hypericum grandifolium]]''}}";
		$title = 'Hypericum sect. Androsaemum';
		$expected = [
			'Hypericum sect. Androsaemum',
			'Hypericum androsaemum',
			'Hypericum hircinum',
			'Hypericum foliosum',
			'Hypericum × inodorum',
			'Hypericum grandifolium',
		];
		$result = $this->p->getAllArticleTitles( $topicBoxWikicode, $title );
		$this->assertSame( $expected, $result );
	}

	public function test_getAllArticleTitles_templatesInWikilink() {
		$topicBoxWikicode =
"{{Featured topic box |title=1989 (Taylor's Version) |count=5 |image=Taylor Swift The Eras Tour 1989 Era Set (53109542801) (cropped).jpg |imagesize=100px 
|lead={{classicon|GA}} ''[[1989 (Taylor's Version)]]''
|column1=
: {{classicon|GA}} \"[[\"Slut!\"|{{-'}}Slut!{{'-}}]]\"
: {{classicon|GA}} \"[[Say Don't Go]]\" 
|column2=
: {{classicon|GA}} \"[[Now That We Don't Talk]]\" 
|column3=
: {{classicon|GA}} \"[[Is It Over Now?]]\" }}";
		$title = 'Hypericum sect. Androsaemum';
		$expected = [
			'1989 (Taylor\'s Version)',
			'"Slut!"',
			'Say Don\'t Go',
			'Now That We Don\'t Talk',
			'Is It Over Now?',
		];
		$result = $this->p->getAllArticleTitles( $topicBoxWikicode, $title );
		$this->assertSame( $expected, $result );
	}

	public function test_checkCounts_normal() {
		$goodArticleCount = 1;
		$featuredArticleCount = 1;
		$allArticleTitles = [ 'a', 'b' ];
		$this->p->checkCounts( $goodArticleCount, $featuredArticleCount, $allArticleTitles );
		$this->expectNotToPerformAssertions();
	}

	public function test_checkCounts_incorrectSum() {
		$goodArticleCount = 1;
		$featuredArticleCount = 1;
		$allArticleTitles = [ 'a', 'b', 'c' ];
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->checkCounts( $goodArticleCount, $featuredArticleCount, $allArticleTitles );
	}

	public function test_checkCounts_zero() {
		$goodArticleCount = 0;
		$featuredArticleCount = 0;
		$allArticleTitles = [];
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->checkCounts( $goodArticleCount, $featuredArticleCount, $allArticleTitles );
	}

	public function test_checkCounts_one() {
		$goodArticleCount = 1;
		$featuredArticleCount = 0;
		$allArticleTitles = [ 'a' ];
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->checkCounts( $goodArticleCount, $featuredArticleCount, $allArticleTitles );
	}

	public function test_decideIfGoodOrFeatured_good() {
		$goodArticleCount = 2;
		$featuredArticleCount = 1;
		$result = $this->p->decideIfGoodOrFeatured( $goodArticleCount, $featuredArticleCount );
		$this->assertSame( 'good', $result );
	}

	public function test_decideIfGoodOrFeatured_featured() {
		$goodArticleCount = 1;
		$featuredArticleCount = 2;
		$result = $this->p->decideIfGoodOrFeatured( $goodArticleCount, $featuredArticleCount );
		$this->assertSame( 'featured', $result );
	}

	public function test_decideIfGoodOrFeatured_equal() {
		$goodArticleCount = 2;
		$featuredArticleCount = 2;
		$result = $this->p->decideIfGoodOrFeatured( $goodArticleCount, $featuredArticleCount );
		$this->assertSame( 'featured', $result );
	}

	public function test_decideIfGoodOrFeatured_zero() {
		$goodArticleCount = 0;
		$featuredArticleCount = 2;
		$result = $this->p->decideIfGoodOrFeatured( $goodArticleCount, $featuredArticleCount );
		$this->assertSame( 'featured', $result );
	}

	public function test_getGoodArticleCount() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize=
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} [[2019 Tour Championship]]
|column3=
:{{Icon|GA}} [[2021 Tour Championship]] }}';
		$result = $this->p->getGoodArticleCount( $topicBoxWikicode );
		$this->assertSame( 2, $result );
	}

	public function test_getFeaturedArticleCount() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize=
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FA}} [[2019 Tour Championship]]
|column2=
:{{Icon|FA}} [[2020 Tour Championship]] }}';
		$result = $this->p->getFeaturedArticleCount( $topicBoxWikicode );
		$this->assertSame( 2, $result );
	}

	public function test_getFeaturedArticleCount_featuredList() {
		$topicBoxWikicode =
'{{Featured topic box |title= |count=4 |image= |imagesize=
|lead={{icon|GA}} [[Tour Championship (snooker)|Tour Championship]]
|column1=
:{{Icon|FL}} [[2019 Tour Championship]]
|column2=
:{{Icon|FA}} [[2020 Tour Championship]] }}';
		$result = $this->p->getFeaturedArticleCount( $topicBoxWikicode );
		$this->assertSame( 2, $result );
	}

	public function test_addTopicToGoingsOn_noOtherTopicsPresent() {
		$goingsOnTitle = 'Wikipedia:Goings-on';
		$goingsOnWikicode =
"* [[:File:White-cheeked Honeyeater - Maddens Plains.jpg|White-cheeked honeyeater]] (1 Sep)

'''[[Wikipedia:Featured topics|Topics]] that gained featured status'''
|}
</div>

==See also==";
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Tour Championship (snooker)';
		$mainArticleTitle = 'Tour Championship (snooker)';
		// September 2, 2021, 07:38:58
		$timestamp = 1630568338;
		$result = $this->p->addTopicToGoingsOn( $goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $mainArticleTitle, $timestamp );
		$this->assertSame(
"* [[:File:White-cheeked Honeyeater - Maddens Plains.jpg|White-cheeked honeyeater]] (1 Sep)

'''[[Wikipedia:Featured topics|Topics]] that gained featured status'''
* [[Wikipedia:Featured topics/Tour Championship (snooker)|Tour Championship (snooker)]] (2 Sep)
|}
</div>

==See also==", $result );
	}

	public function test_addTopicToGoingsOn_otherTopicsPresent_newestLast() {
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
		// September 2, 2021, 07:38:58 UTC
		$timestamp = 1630568338;
		$result = $this->p->addTopicToGoingsOn( $goingsOnTitle, $goingsOnWikicode, $topicWikipediaPageTitle, $mainArticleTitle, $timestamp );
		$this->assertSame(
"* [[:File:White-cheeked Honeyeater - Maddens Plains.jpg|White-cheeked honeyeater]] (1 Sep)

'''[[Wikipedia:Featured topics|Topics]] that gained featured status'''
* [[Wikipedia:Featured topics/Tour Championship (snooker) A|Tour Championship (snooker) A]] (1 Sep)
* [[Wikipedia:Featured topics/Tour Championship (snooker) B|Tour Championship (snooker) B]] (1 Sep)
* [[Wikipedia:Featured topics/Tour Championship (snooker)|Tour Championship (snooker)]] (2 Sep)
|}
</div>

==See also==", $result );
	}

	public function test_getNonMainArticleTitles() {
		$allArticleTitles = [ 'a', 'b', 'c' ];
		$mainArticleTitle = 'b';
		$result = $this->p->getNonMainArticleTitles( $allArticleTitles, $mainArticleTitle );
		$this->assertSame( [ 'a', 'c' ], $result );
	}

	public function test_getWikiProjectBanners_dontAddBannerShellTwice() {
		$title = 'Wikipedia talk:Featured topics/Meet the Woo 2';
		$mainArticleTalkPageWikicode =
'{{WikiProject banner shell|1=
{{WikiProject Albums|class=GA|importance=low}}
{{WikiProject Hip hop|class=GA|importance=low}}
}}';
		$result = $this->p->getWikiProjectBanners( $mainArticleTalkPageWikicode, $title );
		$this->assertSame(
'{{WikiProject banner shell|1=
{{WikiProject Albums}}
{{WikiProject Hip hop}}
}}', $result );
	}

	public function test_getWikiProjectBanners_noParameters() {
		$title = '';
		$mainArticleTalkPageWikicode = '{{WikiProject Snooker}}';
		$result = $this->p->getWikiProjectBanners( $mainArticleTalkPageWikicode, $title );
		$this->assertSame( '{{WikiProject Snooker}}', $result );
	}

	public function test_getWikiProjectBanners_runTrimOnTemplateName() {
		$title = '';
		$mainArticleTalkPageWikicode = '{{WikiProject Snooker }}';
		$result = $this->p->getWikiProjectBanners( $mainArticleTalkPageWikicode, $title );
		$this->assertSame( '{{WikiProject Snooker}}', $result );
	}

	public function test_getWikiProjectBanners_parametersShouldBeRemoved() {
		$title = '';
		$mainArticleTalkPageWikicode = '{{WikiProject Snooker |class=GA|importance=Low}}';
		$result = $this->p->getWikiProjectBanners( $mainArticleTalkPageWikicode, $title );
		$this->assertSame( '{{WikiProject Snooker}}', $result );
	}

	public function test_getWikiProjectBanners_threeBannersShouldGetBannerShell() {
		$title = '';
		$mainArticleTalkPageWikicode =
'{{WikiProject Cue Sports}}
{{WikiProject Biography}}
{{WikiProject Women}}';
		$result = $this->p->getWikiProjectBanners( $mainArticleTalkPageWikicode, $title );
		$this->assertSame(
'{{WikiProject banner shell|1=
{{WikiProject Cue Sports}}
{{WikiProject Biography}}
{{WikiProject Women}}
}}', $result );
	}

	public function test_getWikiProjectBanners_twoBannersShouldGetBannerShell() {
		$title = '';
		$mainArticleTalkPageWikicode =
'{{WikiProject Cue Sports}}
{{WikiProject Biography}}';
		$result = $this->p->getWikiProjectBanners( $mainArticleTalkPageWikicode, $title );
		$this->assertSame(
'{{WikiProject banner shell|1=
{{WikiProject Cue Sports}}
{{WikiProject Biography}}
}}', $result );
	}

	public function test_getWikiProjectBanners_oneBannerShouldNotGetBannerShell() {
		$title = '';
		$mainArticleTalkPageWikicode = '{{WikiProject Cue Sports}}';
		$result = $this->p->getWikiProjectBanners( $mainArticleTalkPageWikicode, $title );
		$this->assertSame( '{{WikiProject Cue Sports}}', $result );
	}

	public function test_getWikiProjectBanners_doNotDetectShellAsWikiProjects() {
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
		$result = $this->p->getWikiProjectBanners( $mainArticleTalkPageWikicode, $title );
		$this->assertSame( '{{WikiProject Cue Sports}}', $result );
	}

	public function test_getTopicTalkPageTitle() {
		$mainArticleTitle = 'Dua Lipa (album)';
		$result = $this->p->getTopicTalkPageTitle( $mainArticleTitle );
		$this->assertSame( 'Wikipedia talk:Featured topics/Dua Lipa (album)', $result );
	}

	public function test_setTopicBoxTitleParameter_noTitle() {
		$topicBoxWikicode = '{{Featured topic box}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParameter( $topicBoxWikicode, $mainArticleTitle );
		$this->assertSame(
'{{Featured topic box
|title=Test article
}}', $result );
	}

	public function test_setTopicBoxTitleParameter_blankTitle() {
		$topicBoxWikicode = '{{Featured topic box|title=}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParameter( $topicBoxWikicode, $mainArticleTitle );
		$this->assertSame( '{{Featured topic box|title=Test article}}', $result );
	}

	public function test_setTopicBoxTitleParameter_alreadyHasTitle() {
		$topicBoxWikicode = '{{Featured topic box|title=Test article}}';
		$mainArticleTitle = 'Test article';
		$result = $this->p->setTopicBoxTitleParameter( $topicBoxWikicode, $mainArticleTitle );
		$this->assertSame( '{{Featured topic box|title=Test article}}', $result );
	}

	public function test_getTopicDescriptionWikicode_simple() {
		$callerPageWikicode =
'===Protected cruisers of France===
In the 1880s and 1890s, the [[French Navy]] built a series of [[protected cruiser]]s, some 33 ships in total. The ships filled a variety of roles, and their varying designs represented the strategic and doctrinal conflicts in the French naval command at that time. The factions included those who favored a strong main fleet in French waters, those who preferred the long-range commerce raiders prescribed by the [[Jeune Ecole]], and those who wanted a fleet based on colonial requirements. Eventually, the type was superseded in French service by more powerful [[armored cruiser]]s.

{{Featured topic box}}';
		$result = $this->p->getTopicDescriptionWikicode( $callerPageWikicode );
		$this->assertSame(
'<noinclude>In the 1880s and 1890s, the [[French Navy]] built a series of [[protected cruiser]]s, some 33 ships in total. The ships filled a variety of roles, and their varying designs represented the strategic and doctrinal conflicts in the French naval command at that time. The factions included those who favored a strong main fleet in French waters, those who preferred the long-range commerce raiders prescribed by the [[Jeune Ecole]], and those who wanted a fleet based on colonial requirements. Eventually, the type was superseded in French service by more powerful [[armored cruiser]]s.</noinclude>', $result );
	}

	public function test_getTopicDescriptionWikicode_hasTemplateInDescription() {
		$callerPageWikicode =
'===Protected cruisers of France===
In the 1880s and 1890s, the [[French Navy]] built a series of [[protected cruiser]]s, some 33 ships in total. The ships filled a variety of roles, and their varying designs represented the strategic and doctrinal conflicts in the French naval command at that time. The factions included those who favored a strong main fleet in French waters, those who preferred the long-range commerce raiders prescribed by the {{lang|fr|[[Jeune Ecole]]}}, and those who wanted a fleet based on colonial requirements. Eventually, the type was superseded in French service by more powerful [[armored cruiser]]s.

{{Featured topic box}}';
		$result = $this->p->getTopicDescriptionWikicode( $callerPageWikicode );
		$this->assertSame(
'<noinclude>In the 1880s and 1890s, the [[French Navy]] built a series of [[protected cruiser]]s, some 33 ships in total. The ships filled a variety of roles, and their varying designs represented the strategic and doctrinal conflicts in the French naval command at that time. The factions included those who favored a strong main fleet in French waters, those who preferred the long-range commerce raiders prescribed by the {{lang|fr|[[Jeune Ecole]]}}, and those who wanted a fleet based on colonial requirements. Eventually, the type was superseded in French service by more powerful [[armored cruiser]]s.</noinclude>', $result );
	}

	public function test_getTopicDescriptionWikicode_commentedNoInclude() {
		$callerPageWikicode =
'
===[[Wikipedia:Featured and good topic candidates/EFL League Two play-offs/archive1|EFL League Two play-offs]]===
<!---<noinclude>--->The [[EFL League Two play-offs]] are a series of play-off matches contested by the association football teams finishing from fourth to seventh in [[EFL League Two]], the fourth tier of English football, and are part of the [[English Football League play-offs]]. As of 2021, the play-offs comprise two semi-finals, where the team finishing third plays the team finishing sixth, and the team finishing fourth plays the team finishing fifth, each conducted as a two-legged tie. The winners of the semi-finals progress to the final which is contested at [[Wembley Stadium]].<!---</noinclude>--->

{{Featured topic box}}';
		$result = $this->p->getTopicDescriptionWikicode( $callerPageWikicode );
		$this->assertSame(
'<noinclude>The [[EFL League Two play-offs]] are a series of play-off matches contested by the association football teams finishing from fourth to seventh in [[EFL League Two]], the fourth tier of English football, and are part of the [[English Football League play-offs]]. As of 2021, the play-offs comprise two semi-finals, where the team finishing third plays the team finishing sixth, and the team finishing fourth plays the team finishing fifth, each conducted as a two-legged tie. The winners of the semi-finals progress to the final which is contested at [[Wembley Stadium]].</noinclude>', $result );
	}

	public function test_getTopicTitle_withTopic() {
		$topicBoxWikicode =
'{{Featured topic box |title=UEFA European Championship finals |count=17 |image=Coupe Henri Delaunay 2017.jpg |imagesize= 
|lead={{icon|FL}} [[List of UEFA European Championship finals|UEFA European Championship finals]]
|column1=
:{{icon|GA}} [[UEFA Euro 2020 Final]] }}';
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle( $topicBoxWikicode, $mainArticleTitle );
		$this->assertSame( 'UEFA European Championship finals', $result );
	}

	public function test_getTopicTitle_withTopic_bold() {
		$topicBoxWikicode = "{{Featured topic box |title='''UEFA European Championship finals''' |count=17}}";
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle( $topicBoxWikicode, $mainArticleTitle );
		$this->assertSame( 'UEFA European Championship finals', $result );
	}

	public function test_getTopicTitle_withTopic_boldItalic() {
		$topicBoxWikicode = "{{Featured topic box |title='''''UEFA European Championship finals''''' |count=17}}";
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle( $topicBoxWikicode, $mainArticleTitle );
		$this->assertSame( 'UEFA European Championship finals', $result );
	}

	public function test_getTopicTitle_withTopic_italic() {
		$topicBoxWikicode = "{{Featured topic box |title=''UEFA European Championship finals'' |count=17}}";
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle( $topicBoxWikicode, $mainArticleTitle );
		$this->assertSame( 'UEFA European Championship finals', $result );
	}

	public function test_getTopicTitle_noTopic() {
		$topicBoxWikicode =
'{{Featured topic box |count=17 |image=Coupe Henri Delaunay 2017.jpg |imagesize= 
|lead={{icon|FL}} [[List of UEFA European Championship finals|UEFA European Championship finals]]
|column1=
:{{icon|GA}} [[UEFA Euro 2020 Final]] }}';
		$mainArticleTitle = 'List of UEFA European Championship finals';
		$result = $this->p->getTopicTitle( $topicBoxWikicode, $mainArticleTitle );
		$this->assertSame( 'List of UEFA European Championship finals', $result );
	}

	public function test_abortIfPromotionTemplateMissingOrDone_promoteDoneNo() {
		$wikicode = '{{User:NovemBot/Promote}}';
		$title = 'Wikipedia:Featured and good topic candidates/NASA Astronaut Group 2/archive1';
		$this->p->abortIfPromotionTemplateMissingOrDone( $wikicode, $title );
		$this->expectNotToPerformAssertions();
	}

	public function test_abortIfPromotionTemplateMissingOrDone_promoteDoneYes() {
		$wikicode = '{{User:NovemBot/Promote|done=yes}}';
		$title = 'Wikipedia:Featured and good topic candidates/NASA Astronaut Group 2/archive1';
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->abortIfPromotionTemplateMissingOrDone( $wikicode, $title );
	}

	public function test_abortIfPromotionTemplateMissingOrDone_noTemplate() {
		$wikicode = 'Test';
		$title = 'Wikipedia:Featured and good topic candidates/NASA Astronaut Group 2/archive1';
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->abortIfPromotionTemplateMissingOrDone( $wikicode, $title );
	}

	public function test_getMainArticleTitle_notPiped() {
		$title = 'Wikipedia:Featured topics/Billboard number-one country songs';
		$topicBoxWikicode =
"{{Featured topic box |title=Billboard number-one country songs |count=78 |image=Country music legends.jpg |imagesize=200 
|lead={{icon|FL}} [[List of Billboard number-one country songs]] }}";
		$result = $this->p->getMainArticleTitle( $topicBoxWikicode, $title );
		$this->assertSame( "List of Billboard number-one country songs", $result );
	}

	public function test_getMainArticleTitle_piped() {
		$title = 'Wikipedia:Featured topics/Billboard number-one country songs';
		$topicBoxWikicode =
"{{Featured topic box |title=Billboard number-one country songs |count=78 |image=Country music legends.jpg |imagesize=200 
|lead={{icon|FL}} [[List of Billboard number-one country songs|''Billboard'' number-one country songs]] }}";
		$result = $this->p->getMainArticleTitle( $topicBoxWikicode, $title );
		$this->assertSame( "List of Billboard number-one country songs", $result );
	}

	public function test_getMainArticleTitle_spaceAtEnd() {
		$title = 'Wikipedia:Featured topics/Billboard number-one country songs';
		$topicBoxWikicode =
"{{Featured topic box |title=Billboard number-one country songs |count=78 |image=Country music legends.jpg |imagesize=200 
|lead={{icon|FL}} [[List of Billboard number-one country songs |''Billboard'' number-one country songs]] }}";
		$result = $this->p->getMainArticleTitle( $topicBoxWikicode, $title );
		$this->assertSame( "List of Billboard number-one country songs", $result );
	}

	public function test_getTemplateFeaturedTopicLogWikicode_goodTopic() {
		$month = 'August';
		$year = '2022';
		$countTemplateWikicode =
"{| class=\"noprint toccolours\" style=\"clear: right; margin: 0 0 1em 1em; font-size: 90%; width: 13em; float: right;\"
|colspan=\"3\"|<span style=\"float:right;\"><small class=\"editlink noprint plainlinksneverexpand\">[{{SERVER}}{{localurl:Template:Featured topic log|action=edit}} edit]</small></span>'''2006'''
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2006|1&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2006|6&nbsp;not&nbsp;promoted]]
|-
|October
|0&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Failed log/October 2006|1&nbsp;not&nbsp;promoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2006|4&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/November 2006|1&nbsp;not&nbsp;promoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2006|1&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2006|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/December 2006|1&nbsp;sup.]]
|-
|colspan=\"3\"|'''2007'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2007|2&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/January 2007|7&nbsp;not&nbsp;promoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2007|1&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2007|2&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2007 log|1&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2007|1&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2007|4&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2007 log|1&nbsp;demoted]]
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2007|2&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2007|1&nbsp;not&nbsp;promoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2007|2&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/May 2007|4&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2007|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2007 log|1&nbsp;kept]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2007|3&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/June 2007|2&nbsp;not&nbsp;promoted]]
|-
|July
|0&nbsp;promoted
|0&nbsp;not&nbsp;promoted
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2007|1&nbsp;promoted]]
|0&nbsp;not&nbsp;promoted
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2007|4&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2007|6&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2007|1&nbsp;sup.]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2007|4&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/October 2007|1&nbsp;not&nbsp;promoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2007|2&nbsp;promoted]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/2007|2&nbsp;sup.]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2007|3&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2007|1&nbsp;not&nbsp;promoted]]
|-
|colspan=\"3\"|'''2008'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2008|3&nbsp;promoted]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/2008|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2008 log|2&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2008|2&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2008|1&nbsp;not&nbsp;promoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2008|4&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2008|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|1&nbsp;sup.]]
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2008|5&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2008|4&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2008 log|1&nbsp;kept]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2008|5&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/May 2008|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|1&nbsp;sup.]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2008|2&nbsp;promoted]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/2008|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2008 log|2&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2008|3&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/July 2008|4&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|1&nbsp;sup.]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2008|7&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/August 2008|5&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|2&nbsp;sup.]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2008|10&nbsp;FT,&nbsp;7&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2008|14&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|3&nbsp;sup.]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2008|2&nbsp;FT,&nbsp;7&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/October 2008|7&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2008 log|1&nbsp;kept]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2008|2&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/November 2008|3&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|4&nbsp;sup.]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2008|7&nbsp;FT,&nbsp;11&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2008|5&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|2&nbsp;sup.]]
|-
|colspan=\"3\"|'''2009'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2009|2&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/January 2009|5&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/January 2009|2&nbsp;sup.]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2009|7&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2009|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/February 2009|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|1&nbsp;kept,&nbsp;1&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2009|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2009|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/March 2009|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|1&nbsp;kept]]
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2009|3&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2009|3&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2009|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2009 log|1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2009|4&nbsp;FT,&nbsp;9&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/June 2009|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/June 2009|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|3&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2009|2&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/July 2009|5&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/July 2009|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|2&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2009|2&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/August 2009|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/August 2009|1&nbsp;sup.]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2009|3&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2009|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/September 2009|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|2&nbsp;kept]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2009|3&nbsp;FT,&nbsp;4&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/October 2009|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|2&nbsp;kept,&nbsp;6&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2009|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/November 2009|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2009 log|1&nbsp;kept]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2009|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2009|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|-
|colspan=\"3\"|'''2010'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2010|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/January 2010|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/January 2010|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2010|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2010|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/February 2010|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;kept,&nbsp;2&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2010|5&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2010|3&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/March 2010|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|1&nbsp;kept,&nbsp;5&nbsp;demoted]]
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2010|1&nbsp;FT,&nbsp;8&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2010|3&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/April 2010|4&nbsp;sup.]]
|-1
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2010|0&nbsp;FT,&nbsp;7&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/May 2010|1&nbsp;sup.]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2010|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2010 log|1&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2010|5&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/July 2010|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/July 2010|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2010|1&nbsp;FT,&nbsp;6&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/August 2010|1&nbsp;sup.]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2010|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2010|4&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2010|3&nbsp;FT,&nbsp;18&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/October 2010|4&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/October 2010|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;kept, 2&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2010|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;kept, 1&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2010|2&nbsp;FT,&nbsp;7&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2010|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/December 2010|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2011'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2011|2&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/January 2011|3&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2011|1&nbsp;FT,&nbsp;11&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2011|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2011|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2011|2&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2011|1&nbsp;FT,&nbsp;9&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2011|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2011|1&nbsp;sup.]]
|0&nbsp;kept, 0&nbsp;demoted
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2011|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2011|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/June 2011|2&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2011|2&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/July 2011|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2011|1&nbsp;FT,&nbsp;8&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/August 2011|2&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2011|2&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2011|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2011|1&nbsp;sup.]]
|0&nbsp;kept, 0&nbsp;demoted
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2011|4&nbsp;FT,&nbsp;6&nbsp;GT]]
|0 not promoted
|[[Wikipedia:Featured topic candidates/Addition log/2011|2&nbsp;sup.]]
|0&nbsp;kept, 0&nbsp;demoted
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2011|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/November 2011|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2011|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2011|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2012'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2012|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2012|0&nbsp;FT,&nbsp;11&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/2012|1&nbsp;sup.]]
|0&nbsp;kept, 0&nbsp;demoted
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2012|2&nbsp;FT,&nbsp;0&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2012|0&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2012|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2012 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2012|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2012 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2012|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2012|0&nbsp;FT,&nbsp;14&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2012|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2012|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2012 log|0&nbsp;kept, 4&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2012|2&nbsp;FT,&nbsp;0&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2012|1&nbsp;FT,&nbsp;6&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2012 log|2&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2012|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2012|2&nbsp;FT,&nbsp;4&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2012|1&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2012|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2012|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2012 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2013'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2013|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2013|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2013|2&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2013|2&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|2&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2013|0&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2013|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2013|1&nbsp;FT,&nbsp;8&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|3&nbsp;kept, 2&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2013|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2013|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2013|4&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2013|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2013|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2014'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2014|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2014|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2014|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2014|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2014|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2014|2&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2014|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2014|4&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2014|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2014|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2014|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2014|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2015'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2015|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2015|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2015|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2015|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2015|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2015|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2015|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2015|2&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2015|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2015|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2015|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2016'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2016|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2016|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2016|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2016|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2016|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2016|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2016|0&nbsp;FT,&nbsp;7&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2016|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2016|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|1&nbsp;kept, 2&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2016|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2017'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2017|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2017|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2017|4&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2017|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2017|1&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2017|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2017|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2017|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2017|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2017|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2017|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2017|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2018'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2018|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2018|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2018|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2018|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2018|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2018|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2018|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2018|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2018|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2018|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2018|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2018|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2019'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2019|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|4&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|4&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2019|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2019|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2019|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2019|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2019|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2019|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2019|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 3&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2019|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2019|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2020'''
|-
|January
|0&nbsp;FT,&nbsp;0&nbsp;GT
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2020|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 5&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2020|3&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2020|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|2&nbsp;kept, 4&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2020|0&nbsp;FT,&nbsp;8&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2020|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2020|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2020|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2020|0&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2020|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2021'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2021|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2021|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2021|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2021|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2021|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2021|2&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2021|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2021|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2021|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2021|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|November
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|2&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2022'''
|-
|January
|[[Wikipedia:Featured and good topic candidates/Featured log/January 2022|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|2&nbsp;kept, 3&nbsp;demoted]]
|-
|February
|1&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured and good topic candidates/Featured log/March 2022|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 3&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured and good topic candidates/Featured log/April 2022|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured and good topic candidates/Featured log/May 2022|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured and good topic candidates/Featured log/June 2022|2&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured and good topic candidates/Featured log/July 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured and good topic candidates/Featured log/August 2022|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured and good topic candidates/Featured log/September 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured and good topic candidates/Featured log/October 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured and good topic candidates/Featured log/November 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured and good topic candidates/Featured log/December 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|}
[[Category:Featured topic candidate log]]
<noinclude>
[[Category:Wikipedia featured topics templates]]
</noinclude>
";
		$goodOrFeatured = 'good';
		$result = $this->p->getTemplateFeaturedTopicLogWikicode( $month, $year, $countTemplateWikicode, $goodOrFeatured );
		$expected =
"{| class=\"noprint toccolours\" style=\"clear: right; margin: 0 0 1em 1em; font-size: 90%; width: 13em; float: right;\"
|colspan=\"3\"|<span style=\"float:right;\"><small class=\"editlink noprint plainlinksneverexpand\">[{{SERVER}}{{localurl:Template:Featured topic log|action=edit}} edit]</small></span>'''2006'''
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2006|1&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2006|6&nbsp;not&nbsp;promoted]]
|-
|October
|0&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Failed log/October 2006|1&nbsp;not&nbsp;promoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2006|4&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/November 2006|1&nbsp;not&nbsp;promoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2006|1&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2006|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/December 2006|1&nbsp;sup.]]
|-
|colspan=\"3\"|'''2007'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2007|2&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/January 2007|7&nbsp;not&nbsp;promoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2007|1&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2007|2&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2007 log|1&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2007|1&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2007|4&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2007 log|1&nbsp;demoted]]
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2007|2&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2007|1&nbsp;not&nbsp;promoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2007|2&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/May 2007|4&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2007|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2007 log|1&nbsp;kept]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2007|3&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/June 2007|2&nbsp;not&nbsp;promoted]]
|-
|July
|0&nbsp;promoted
|0&nbsp;not&nbsp;promoted
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2007|1&nbsp;promoted]]
|0&nbsp;not&nbsp;promoted
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2007|4&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2007|6&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2007|1&nbsp;sup.]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2007|4&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/October 2007|1&nbsp;not&nbsp;promoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2007|2&nbsp;promoted]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/2007|2&nbsp;sup.]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2007|3&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2007|1&nbsp;not&nbsp;promoted]]
|-
|colspan=\"3\"|'''2008'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2008|3&nbsp;promoted]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/2008|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2008 log|2&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2008|2&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2008|1&nbsp;not&nbsp;promoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2008|4&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2008|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|1&nbsp;sup.]]
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2008|5&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2008|4&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2008 log|1&nbsp;kept]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2008|5&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/May 2008|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|1&nbsp;sup.]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2008|2&nbsp;promoted]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/2008|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2008 log|2&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2008|3&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/July 2008|4&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|1&nbsp;sup.]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2008|7&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Failed log/August 2008|5&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|2&nbsp;sup.]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2008|10&nbsp;FT,&nbsp;7&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2008|14&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|3&nbsp;sup.]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2008|2&nbsp;FT,&nbsp;7&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/October 2008|7&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2008 log|1&nbsp;kept]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2008|2&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/November 2008|3&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|4&nbsp;sup.]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2008|7&nbsp;FT,&nbsp;11&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2008|5&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2008|2&nbsp;sup.]]
|-
|colspan=\"3\"|'''2009'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2009|2&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/January 2009|5&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/January 2009|2&nbsp;sup.]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2009|7&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2009|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/February 2009|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|1&nbsp;kept,&nbsp;1&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2009|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2009|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/March 2009|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|1&nbsp;kept]]
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2009|3&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2009|3&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2009|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2009 log|1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2009|4&nbsp;FT,&nbsp;9&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/June 2009|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/June 2009|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|3&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2009|2&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/July 2009|5&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/July 2009|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|2&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2009|2&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/August 2009|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/August 2009|1&nbsp;sup.]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2009|3&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2009|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/September 2009|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|2&nbsp;kept]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2009|3&nbsp;FT,&nbsp;4&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/October 2009|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2009 log|2&nbsp;kept,&nbsp;6&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2009|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/November 2009|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2009 log|1&nbsp;kept]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2009|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2009|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|-
|colspan=\"3\"|'''2010'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2010|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/January 2010|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/January 2010|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2010|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2010|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/February 2010|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;kept,&nbsp;2&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2010|5&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2010|3&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/March 2010|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|1&nbsp;kept,&nbsp;5&nbsp;demoted]]
|-
|April 
|[[Wikipedia:Featured topic candidates/Featured log/April 2010|1&nbsp;FT,&nbsp;8&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2010|3&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/April 2010|4&nbsp;sup.]]
|-1
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2010|0&nbsp;FT,&nbsp;7&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/May 2010|1&nbsp;sup.]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2010|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2010 log|1&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2010|5&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/July 2010|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/July 2010|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2010|1&nbsp;FT,&nbsp;6&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/August 2010|1&nbsp;sup.]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2010|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2010|4&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2010|3&nbsp;FT,&nbsp;18&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/October 2010|4&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/October 2010|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;kept, 2&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2010|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2010 log|2&nbsp;kept, 1&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2010|2&nbsp;FT,&nbsp;7&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2010|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/December 2010|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2010 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2011'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2011|2&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/January 2011|3&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2011|1&nbsp;FT,&nbsp;11&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/February 2011|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2011|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/March 2011|2&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2011|1&nbsp;FT,&nbsp;9&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/April 2011|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2011|1&nbsp;sup.]]
|0&nbsp;kept, 0&nbsp;demoted
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2011|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2011|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/June 2011|2&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2011|2&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/July 2011|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2011|1&nbsp;FT,&nbsp;8&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/August 2011|2&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2011|2&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/September 2011|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2011|1&nbsp;sup.]]
|0&nbsp;kept, 0&nbsp;demoted
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2011|4&nbsp;FT,&nbsp;6&nbsp;GT]]
|0 not promoted
|[[Wikipedia:Featured topic candidates/Addition log/2011|2&nbsp;sup.]]
|0&nbsp;kept, 0&nbsp;demoted
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2011|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/November 2011|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2011|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/December 2011|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2011 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2012'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2012|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2012|0&nbsp;FT,&nbsp;11&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|[[Wikipedia:Featured topic candidates/Addition log/2012|1&nbsp;sup.]]
|0&nbsp;kept, 0&nbsp;demoted
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2012|2&nbsp;FT,&nbsp;0&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2012|0&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2012|1&nbsp;not&nbsp;promoted]]
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2012 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2012|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2012 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2012|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2012|0&nbsp;FT,&nbsp;14&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2012|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2012|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2012 log|0&nbsp;kept, 4&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2012|2&nbsp;FT,&nbsp;0&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2012|1&nbsp;FT,&nbsp;6&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|[[Wikipedia:Featured topic removal candidates/2012 log|2&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2012|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2012|2&nbsp;FT,&nbsp;4&nbsp;GT]]
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2012|1&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2012|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2012|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2012 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2013'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2013|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2013|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2013|2&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2013|2&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|2&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2013|0&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2013|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2013|1&nbsp;FT,&nbsp;8&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|3&nbsp;kept, 2&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2013|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2013|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2013|4&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2013|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2013|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2013|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2013|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2013 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2014'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2014|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2014|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2014|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2014|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2014|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2014|2&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2014|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2014|4&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2014|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2014|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2014|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2014|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2014|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2014|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2014 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2015'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2015|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2015|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2015|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2015|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2015|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2015|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2015|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2015|2&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2015|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2015|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2015|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2015|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2015|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2015 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2016'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2016|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2016|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2016|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2016|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2016|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2016|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2016|0&nbsp;FT,&nbsp;7&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2016|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2016|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|1&nbsp;kept, 2&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2016|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2016|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2016|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2016 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2017'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2017|2&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2017|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2017|4&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2017|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2017|1&nbsp;FT,&nbsp;6&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2017|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2017|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2017|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2017|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2017|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2017|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2017|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2017|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2017|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2017 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2018'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2018|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2018|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2018|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2018|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2018|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2018|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2018|1&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2018|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2018|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2018|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2018|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2018|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2018|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2018|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2018 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2019'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2019|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|4&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|4&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2019|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2019|1&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2019|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2019|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2019|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2019|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2019|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 3&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2019|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured topic candidates/Featured log/December 2019|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2019|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2019|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2019 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|colspan=\"3\"|'''2020'''
|-
|January
|0&nbsp;FT,&nbsp;0&nbsp;GT
|0&nbsp;not&nbsp;promoted
|0&nbsp;sup.
|0&nbsp;kept, 0&nbsp;demoted
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2020|1&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 5&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2020|3&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|April
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|1&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2020|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|3&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|2&nbsp;kept, 4&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2020|0&nbsp;FT,&nbsp;8&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2020|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2020|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2020|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2020|0&nbsp;FT,&nbsp;5&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|2&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured topic candidates/Featured log/November 2020|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured topic candidates/Failed log/2020|2&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|0&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured topic candidates/Failed log/2020|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured topic candidates/Addition log/2020|1&nbsp;sup.]]
|[[Wikipedia:Featured topic removal candidates/2020 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2021'''
|-
|January
|[[Wikipedia:Featured topic candidates/Featured log/January 2021|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|February
|[[Wikipedia:Featured topic candidates/Featured log/February 2021|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured topic candidates/Featured log/March 2021|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured topic candidates/Featured log/April 2021|0&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured topic candidates/Featured log/May 2021|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured topic candidates/Featured log/June 2021|2&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 2&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured topic candidates/Featured log/July 2021|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured topic candidates/Featured log/August 2021|0&nbsp;FT,&nbsp;3&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|1&nbsp;kept, 0&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured topic candidates/Featured log/September 2021|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured topic candidates/Featured log/October 2021|1&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|November
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|0&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured and good topic candidates/Failed log/2021|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2021|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2021 log|2&nbsp;kept, 1&nbsp;demoted]]
|-
|colspan=\"3\"|'''2022'''
|-
|January
|[[Wikipedia:Featured and good topic candidates/Featured log/January 2022|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|2&nbsp;kept, 3&nbsp;demoted]]
|-
|February
|1&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured and good topic candidates/Featured log/March 2022|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 3&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured and good topic candidates/Featured log/April 2022|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured and good topic candidates/Featured log/May 2022|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured and good topic candidates/Featured log/June 2022|2&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured and good topic candidates/Featured log/July 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured and good topic candidates/Featured log/August 2022|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured and good topic candidates/Featured log/September 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured and good topic candidates/Featured log/October 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured and good topic candidates/Featured log/November 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured and good topic candidates/Featured log/December 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|}
[[Category:Featured topic candidate log]]
<noinclude>
[[Category:Wikipedia featured topics templates]]
</noinclude>
";
		$this->assertSame( $expected, $result );
	}

	public function test_getTemplateFeaturedTopicLogWikicode_featuredTopic() {
		$month = 'August';
		$year = '2022';
		$countTemplateWikicode =
"{| class=\"noprint toccolours\" style=\"clear: right; margin: 0 0 1em 1em; font-size: 90%; width: 13em; float: right;\"
|colspan=\"3\"|'''2022'''
|-
|January
|[[Wikipedia:Featured and good topic candidates/Featured log/January 2022|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|2&nbsp;kept, 3&nbsp;demoted]]
|-
|February
|1&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured and good topic candidates/Featured log/March 2022|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 3&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured and good topic candidates/Featured log/April 2022|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured and good topic candidates/Featured log/May 2022|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured and good topic candidates/Featured log/June 2022|2&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured and good topic candidates/Featured log/July 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured and good topic candidates/Featured log/August 2022|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured and good topic candidates/Featured log/September 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured and good topic candidates/Featured log/October 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured and good topic candidates/Featured log/November 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured and good topic candidates/Featured log/December 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|}
[[Category:Featured topic candidate log]]
<noinclude>
[[Category:Wikipedia featured topics templates]]
</noinclude>
";
		$goodOrFeatured = 'featured';
		$result = $this->p->getTemplateFeaturedTopicLogWikicode( $month, $year, $countTemplateWikicode, $goodOrFeatured );
		$expected =
"{| class=\"noprint toccolours\" style=\"clear: right; margin: 0 0 1em 1em; font-size: 90%; width: 13em; float: right;\"
|colspan=\"3\"|'''2022'''
|-
|January
|[[Wikipedia:Featured and good topic candidates/Featured log/January 2022|0&nbsp;FT,&nbsp;4&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|2&nbsp;kept, 3&nbsp;demoted]]
|-
|February
|1&nbsp;FT,&nbsp;0&nbsp;GT
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|March
|[[Wikipedia:Featured and good topic candidates/Featured log/March 2022|0&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|1&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 3&nbsp;demoted]]
|-
|April
|[[Wikipedia:Featured and good topic candidates/Featured log/April 2022|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|2&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|May
|[[Wikipedia:Featured and good topic candidates/Featured log/May 2022|1&nbsp;FT,&nbsp;1&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|June
|[[Wikipedia:Featured and good topic candidates/Featured log/June 2022|2&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|July
|[[Wikipedia:Featured and good topic candidates/Featured log/July 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|August
|[[Wikipedia:Featured and good topic candidates/Featured log/August 2022|1&nbsp;FT,&nbsp;2&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|1&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 1&nbsp;demoted]]
|-
|September
|[[Wikipedia:Featured and good topic candidates/Featured log/September 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|October
|[[Wikipedia:Featured and good topic candidates/Featured log/October 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|November
|[[Wikipedia:Featured and good topic candidates/Featured log/November 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|-
|December
|[[Wikipedia:Featured and good topic candidates/Featured log/December 2022|0&nbsp;FT,&nbsp;0&nbsp;GT]]
|[[Wikipedia:Featured and good topic candidates/Failed log/2022|0&nbsp;not&nbsp;promoted]]
|[[Wikipedia:Featured and good topic candidates/Addition log/2022|0&nbsp;sup.]]
|[[Wikipedia:Featured and good topic removal candidates/2022 log|0&nbsp;kept, 0&nbsp;demoted]]
|}
[[Category:Featured topic candidate log]]
<noinclude>
[[Category:Wikipedia featured topics templates]]
</noinclude>
";
		$this->assertSame( $expected, $result );
	}

	public function test_updateArticleHistory_oneTopic() {
		$talkPageWikicode = trim( '

{{ArticleHistory
|action1=GAN
|action1date=07:05, 14 August 2020
|action1link=/GA1
|action1result=listed
|action1oldid=971810839

|topic=music
|currentstatus=GA
}}

		' );
		$nextActionNumber = 2;
		$goodOrFeatured = 'good';
		$datetime = '15:11, 24 November 2022';
		$mainArticleTitle = 'Jesus Is King';
		$topicTitle = 'Jesus Is King';
		$articleTitle = 'Jesus Is King';
		$talkPageTitle = 'Talk:Jesus Is King';
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Jesus Is King/archive1';
		$oldid = 1119199461;
		$result = $this->p->updateArticleHistory(
			$talkPageWikicode,
			$nextActionNumber,
			$goodOrFeatured,
			$datetime,
			$mainArticleTitle,
			$topicTitle,
			$articleTitle,
			$talkPageTitle,
			$nominationPageTitle,
			$oldid
		);
		$expected = trim( '

{{ArticleHistory
|action1=GAN
|action1date=07:05, 14 August 2020
|action1link=/GA1
|action1result=listed
|action1oldid=971810839

|topic=music
|currentstatus=GA

|action2 = GTC
|action2date = 15:11, 24 November 2022
|action2link = Wikipedia:Featured and good topic candidates/Jesus Is King/archive1
|action2result = promoted
|action2oldid = 1119199461
|ftname = Jesus Is King
|ftmain = yes
}}

		' );
		$this->assertSame( $expected, $result );
	}

	public function test_updateArticleHistory_twoTopics() {
		$talkPageWikicode = trim( '

{{ArticleHistory
|action1=GAN
|action1date=07:05, 14 August 2020
|action1link=/GA1
|action1result=listed
|action1oldid=971810839

|action2=GTC
|action2date=03:11, 13 January 2021 (UTC)
|action2link=Wikipedia:Featured and good topic candidates/Kanye West studio albums/archive2
|action2result=promoted

|ftname=Kanye West studio albums

|topic=music
|currentstatus=GA
}}

		' );
		$nextActionNumber = 3;
		$goodOrFeatured = 'good';
		$datetime = '15:11, 24 November 2022';
		$mainArticleTitle = 'Jesus Is King';
		$topicTitle = 'Jesus Is King';
		$articleTitle = 'Jesus Is King';
		$talkPageTitle = 'Talk:Jesus Is King';
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Jesus Is King/archive1';
		$oldid = 1119199461;
		$result = $this->p->updateArticleHistory(
			$talkPageWikicode,
			$nextActionNumber,
			$goodOrFeatured,
			$datetime,
			$mainArticleTitle,
			$topicTitle,
			$articleTitle,
			$talkPageTitle,
			$nominationPageTitle,
			$oldid
		);
		$expected = trim( '

{{ArticleHistory
|action1=GAN
|action1date=07:05, 14 August 2020
|action1link=/GA1
|action1result=listed
|action1oldid=971810839

|action2=GTC
|action2date=03:11, 13 January 2021 (UTC)
|action2link=Wikipedia:Featured and good topic candidates/Kanye West studio albums/archive2
|action2result=promoted

|ftname=Kanye West studio albums

|topic=music
|currentstatus=GA

|action3 = GTC
|action3date = 15:11, 24 November 2022
|action3link = Wikipedia:Featured and good topic candidates/Jesus Is King/archive1
|action3result = promoted
|action3oldid = 1119199461
|ft2name = Jesus Is King
|ft2main = yes
}}

		' );
		$this->assertSame( $expected, $result );
	}

	public function test_updateArticleHistory_dontMisreadSimilarTemplateGAList() {
		$talkPageWikicode = trim( '

{{ArticleHistory
|action1=GAN
|action1date=07:05, 14 August 2020
|action1link=/GA1
|action1result=listed
|action1oldid=971810839

|topic=music
|currentstatus=GA
}} {{GAList/check|aye}}

		' );
		$nextActionNumber = 2;
		$goodOrFeatured = 'good';
		$datetime = '15:11, 24 November 2022';
		$mainArticleTitle = 'Jesus Is King';
		$topicTitle = 'Jesus Is King';
		$articleTitle = 'Jesus Is King';
		$talkPageTitle = 'Talk:Jesus Is King';
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Jesus Is King/archive1';
		$oldid = 1119199461;
		$result = $this->p->updateArticleHistory(
			$talkPageWikicode,
			$nextActionNumber,
			$goodOrFeatured,
			$datetime,
			$mainArticleTitle,
			$topicTitle,
			$articleTitle,
			$talkPageTitle,
			$nominationPageTitle,
			$oldid
		);
		$expected = trim( '

{{ArticleHistory
|action1=GAN
|action1date=07:05, 14 August 2020
|action1link=/GA1
|action1result=listed
|action1oldid=971810839

|topic=music
|currentstatus=GA

|action2 = GTC
|action2date = 15:11, 24 November 2022
|action2link = Wikipedia:Featured and good topic candidates/Jesus Is King/archive1
|action2result = promoted
|action2oldid = 1119199461
|ftname = Jesus Is King
|ftmain = yes
}} {{GAList/check|aye}}

		' );
		$this->assertSame( $expected, $result );
	}

	public function test_getNextFTNumber_zeroExistingTopics() {
		$talkPageWikicode = '';
		$result = $this->p->getNextFTNumber( $talkPageWikicode );
		$expected = '';
		$this->assertSame( $expected, $result );
	}

	public function test_getNextFTNumber_oneExistingTopics() {
		$talkPageWikicode = '|ftname=';
		$result = $this->p->getNextFTNumber( $talkPageWikicode );
		$expected = 2;
		$this->assertSame( $expected, $result );
	}

	public function test_getNextFTNumber_twoExistingTopics() {
		$talkPageWikicode = '|ftname=  |ft2name=';
		$result = $this->p->getNextFTNumber( $talkPageWikicode );
		$expected = 3;
		$this->assertSame( $expected, $result );
	}

	public function test_getNextFTNumber_threeExistingTopics() {
		$talkPageWikicode = '|ftname=  |ft2name=  |ft3name=';
		$result = $this->p->getNextFTNumber( $talkPageWikicode );
		$expected = 4;
		$this->assertSame( $expected, $result );
	}

	public function test_markDoneAndSuccessful_normal() {
		$nominationPageWikicode = <<<WIKICODE
=== Topic name ===
* Test
* {{User:NovemBot/Promote}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
WIKICODE;
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Overview of Ben&Ben/archive1';
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Overview of Ben&Ben';
		$goodOrFeatured = 'featured';
		$result = $this->p->markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured );
		$expected =
<<<WIKICODE
=== Topic name ===
{{Archive top|result = The topic was '''promoted''' by {{noping|Aza24}} via ~~~~}}
* Test
* {{User:NovemBot/Promote|done=yes}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
* {{Done}}. Promotion completed successfully. Don't forget to add <code><nowiki>{{Wikipedia:Featured topics/Overview of Ben&Ben}}</nowiki></code> to the appropriate section of [[Wikipedia:Featured topics]]. ~~~~
{{Archive bottom}}
WIKICODE;
		$this->assertSame( $expected, $result );
	}

	public function test_markDoneAndSuccessful_whitespaceAtTop() {
		$nominationPageWikicode = <<<WIKICODE

=== Topic name ===
* Test
* {{User:NovemBot/Promote}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
WIKICODE;
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Overview of Ben&Ben/archive1';
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Overview of Ben&Ben';
		$goodOrFeatured = 'featured';
		$result = $this->p->markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured );
		$expected =
<<<WIKICODE

=== Topic name ===
{{Archive top|result = The topic was '''promoted''' by {{noping|Aza24}} via ~~~~}}
* Test
* {{User:NovemBot/Promote|done=yes}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
* {{Done}}. Promotion completed successfully. Don't forget to add <code><nowiki>{{Wikipedia:Featured topics/Overview of Ben&Ben}}</nowiki></code> to the appropriate section of [[Wikipedia:Featured topics]]. ~~~~
{{Archive bottom}}
WIKICODE;
		$this->assertSame( $expected, $result );
	}

	public function test_markDoneAndSuccessful_categories() {
		$nominationPageWikicode = <<<WIKICODE
=== Topic name ===
* Test
* {{User:NovemBot/Promote}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
[[Category:Marvel Cinematic Universe task force|Featured topics]]
WIKICODE;
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Overview of Ben&Ben/archive1';
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Overview of Ben&Ben';
		$goodOrFeatured = 'featured';
		$result = $this->p->markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured );
		$expected =
<<<WIKICODE
=== Topic name ===
{{Archive top|result = The topic was '''promoted''' by {{noping|Aza24}} via ~~~~}}
* Test
* {{User:NovemBot/Promote|done=yes}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
* {{Done}}. Promotion completed successfully. Don't forget to add <code><nowiki>{{Wikipedia:Featured topics/Overview of Ben&Ben}}</nowiki></code> to the appropriate section of [[Wikipedia:Featured topics]]. ~~~~
{{Archive bottom}}
[[Category:Marvel Cinematic Universe task force|Featured topics]]
WIKICODE;
		$this->assertSame( $expected, $result );
	}

	public function test_markDoneAndSuccessful_noIncludeAndCategories() {
		$nominationPageWikicode = <<<WIKICODE
=== Topic name ===
* Test
* {{User:NovemBot/Promote}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
<noinclude>
[[Category:Marvel Cinematic Universe task force|Featured topics]]
</noinclude>
WIKICODE;
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Overview of Ben&Ben/archive1';
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Overview of Ben&Ben';
		$goodOrFeatured = 'featured';
		$result = $this->p->markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured );
		$expected =
<<<WIKICODE
=== Topic name ===
{{Archive top|result = The topic was '''promoted''' by {{noping|Aza24}} via ~~~~}}
* Test
* {{User:NovemBot/Promote|done=yes}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
* {{Done}}. Promotion completed successfully. Don't forget to add <code><nowiki>{{Wikipedia:Featured topics/Overview of Ben&Ben}}</nowiki></code> to the appropriate section of [[Wikipedia:Featured topics]]. ~~~~
{{Archive bottom}}
<noinclude>
[[Category:Marvel Cinematic Universe task force|Featured topics]]
</noinclude>
WIKICODE;
		$this->assertSame( $expected, $result );
	}

	public function test_markDoneAndSuccessful_missingTemplate() {
		$nominationPageWikicode = '* Test';
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Overview of Ben&Ben/archive1';
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Overview of Ben&Ben';
		$goodOrFeatured = 'featured';
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured );
	}

	public function test_markDoneAndSuccessful_wrongHeading() {
		$nominationPageWikicode = <<<WIKICODE
== Topic name ==
* Test
* {{User:NovemBot/Promote}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
<noinclude>
[[Category:Marvel Cinematic Universe task force|Featured topics]]
</noinclude>
WIKICODE;
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Overview of Ben&Ben/archive1';
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Overview of Ben&Ben';
		$goodOrFeatured = 'featured';
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured );
	}

	public function test_markDoneAndSuccessful_missingHeading() {
		$nominationPageWikicode = <<<WIKICODE
* Test
* {{User:NovemBot/Promote}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 05:20, 1 April 2024 (UTC)
<noinclude>
[[Category:Marvel Cinematic Universe task force|Featured topics]]
</noinclude>
WIKICODE;
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/Overview of Ben&Ben/archive1';
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/Overview of Ben&Ben';
		$goodOrFeatured = 'featured';
		$this->expectException( GiveUpOnThisTopic::class );
		$this->p->markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured );
	}

	public function test_markDoneAndSuccessful_normal2() {
		$nominationPageWikicode = <<<WIKICODE

===[[Wikipedia:Featured and good topic candidates/I&#39;m Breathless/archive1|I'm Breathless]]===
<!---<noinclude>--->'''''I'm Breathless''''' is an album released on May 22, 1990, by [[Sire Records]] to accompany the film ''[[Dick Tracy (1990 film)|Dick Tracy]]''. The album contains three songs written by [[Stephen Sondheim]], which were used in the film, in addition to several songs co-written by Madonna that were inspired by but not included in the film. Madonna starred as [[Breathless Mahoney]] alongside her then-boyfriend [[Warren Beatty]] who played the title role, [[Dick Tracy]]. After filming was complete, Madonna began work on the album, with Sondheim, producer [[Patrick Leonard]] and engineer [[Bill Bottrell]]. In support of both ''I'm Breathless'' and her previous album, ''[[Like a Prayer (album)|Like a Prayer]]'', Madonna embarked on the [[Blond Ambition World Tour]] where a section was dedicated to the songs from the album.<!---</noinclude>--->

{{Featured topic box |title=I'm Breathless |count=8 |image=NowImFollowingYouUnderGround (cropped2).jpg |imagesize=90 
|lead={{icon|GA}} ''[[I'm Breathless]]''
|column1=
:{{icon|GA}} "[[Vogue (Madonna song)|Vogue]]"
:{{icon|GA}} "[[Hanky Panky (Madonna song)|Hanky Panky]]"
:{{icon|GA}} "[[Sooner or Later (Madonna song)|Sooner or Later]]" 
|column2=
:{{icon|GA}}  [[Blond Ambition World Tour]] 
:{{icon|GA}} ''[[Blond Ambition World Tour Live]]''
:{{icon|GA}} ''[[Madonna: Truth or Dare]]'' }}
::<small>''Contributor(s): {{u|Chrishm21}}, {{u|11JORN}}, {{u|IndianBio}}, {{u|Wildroot}}''</small>
Another work from [[WP:MADONNA]], the soundtrack to ''[[Dick Tracy (1990 film)|Dick Tracy]]'' with all related articles - the movie, the Oscar-winning song out of it, two singles not related to the film, the album's accompanying world tour and the two movies that it inspired (a concert video and a documentary). [[User:Igordebraga|igordebraga]] [[User_talk:Igordebraga|≠]] 19:09, 21 April 2024 (UTC) --[[User:Igordebraga|igordebraga]] [[User_talk:Igordebraga|≠]] 19:09, 21 April 2024 (UTC) 
<noinclude>[[Category:Featured topic nominations]] [[Category:Featured topic nominations/2024]]</noinclude>
*'''Comment''' – I am not yet convinced that the film article, ''[[Dick Tracy (1990 film)|Dick Tracy]]'', should be included. The film could probably have its own topic which might include [[I'm Breathless|this album]], the [[Dick Tracy (soundtrack)|motion picture soundtrack]] and possibly the [[Dick Tracy (video game)|successive video games]]. The album should be considered a subsidiarity of the film, not the other way around; that being said, ''I'm Breathless'' should still be a topic. [[User:Idiosincrático|Idiosincrático]] ([[User talk:Idiosincrático|talk]]) 02:13, 1 May 2024 (UTC)
** Duly noted. Removing ''Dick Tracy'', albeit if others disagree with you I'll bring it back. [[User:Igordebraga|igordebraga]] [[User_talk:Igordebraga|≠]] 16:25, 3 May 2024 (UTC)
***'''Support''' – [[User:Idiosincrático|Idiosincrático]] ([[User talk:Idiosincrático|talk]]) 18:32, 5 June 2024 (UTC)
*'''Comment''' Hi. I'm Breathless' impact section might need a second look. I added it, but I'm not that good in grammar. Thanks. --[[User:Apoxyomenus|Apoxyomenus]] ([[User talk:Apoxyomenus|talk]]) 03:17, 8 May 2024 (UTC)
*'''Support''' - now that the topic has been simplified per Idiosincrático's suggestion. [[User:Pseud 14|Pseud 14]] ([[User talk:Pseud 14|talk]]) 17:19, 20 May 2024 (UTC)
* {{User:NovemBot/Promote}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 00:40, 10 June 2024 (UTC)

WIKICODE;
		$nominationPageTitle = 'Wikipedia:Featured and good topic candidates/I\'m Breathless/archive1';
		$topicWikipediaPageTitle = 'Wikipedia:Featured topics/I\'m Breathless';
		$goodOrFeatured = 'good';
		$result = $this->p->markDoneAndSuccessful( $nominationPageWikicode, $nominationPageTitle, $topicWikipediaPageTitle, $goodOrFeatured );
		$expected =
<<<WIKICODE

===[[Wikipedia:Featured and good topic candidates/I&#39;m Breathless/archive1|I'm Breathless]]===
{{Archive top|result = The topic was '''promoted''' by {{noping|Aza24}} via ~~~~}}
<!---<noinclude>--->'''''I'm Breathless''''' is an album released on May 22, 1990, by [[Sire Records]] to accompany the film ''[[Dick Tracy (1990 film)|Dick Tracy]]''. The album contains three songs written by [[Stephen Sondheim]], which were used in the film, in addition to several songs co-written by Madonna that were inspired by but not included in the film. Madonna starred as [[Breathless Mahoney]] alongside her then-boyfriend [[Warren Beatty]] who played the title role, [[Dick Tracy]]. After filming was complete, Madonna began work on the album, with Sondheim, producer [[Patrick Leonard]] and engineer [[Bill Bottrell]]. In support of both ''I'm Breathless'' and her previous album, ''[[Like a Prayer (album)|Like a Prayer]]'', Madonna embarked on the [[Blond Ambition World Tour]] where a section was dedicated to the songs from the album.<!---</noinclude>--->

{{Featured topic box |title=I'm Breathless |count=8 |image=NowImFollowingYouUnderGround (cropped2).jpg |imagesize=90 
|lead={{icon|GA}} ''[[I'm Breathless]]''
|column1=
:{{icon|GA}} "[[Vogue (Madonna song)|Vogue]]"
:{{icon|GA}} "[[Hanky Panky (Madonna song)|Hanky Panky]]"
:{{icon|GA}} "[[Sooner or Later (Madonna song)|Sooner or Later]]" 
|column2=
:{{icon|GA}}  [[Blond Ambition World Tour]] 
:{{icon|GA}} ''[[Blond Ambition World Tour Live]]''
:{{icon|GA}} ''[[Madonna: Truth or Dare]]'' }}
::<small>''Contributor(s): {{u|Chrishm21}}, {{u|11JORN}}, {{u|IndianBio}}, {{u|Wildroot}}''</small>
Another work from [[WP:MADONNA]], the soundtrack to ''[[Dick Tracy (1990 film)|Dick Tracy]]'' with all related articles - the movie, the Oscar-winning song out of it, two singles not related to the film, the album's accompanying world tour and the two movies that it inspired (a concert video and a documentary). [[User:Igordebraga|igordebraga]] [[User_talk:Igordebraga|≠]] 19:09, 21 April 2024 (UTC) --[[User:Igordebraga|igordebraga]] [[User_talk:Igordebraga|≠]] 19:09, 21 April 2024 (UTC) 
<noinclude>[[Category:Featured topic nominations]] [[Category:Featured topic nominations/2024]]</noinclude>
*'''Comment''' – I am not yet convinced that the film article, ''[[Dick Tracy (1990 film)|Dick Tracy]]'', should be included. The film could probably have its own topic which might include [[I'm Breathless|this album]], the [[Dick Tracy (soundtrack)|motion picture soundtrack]] and possibly the [[Dick Tracy (video game)|successive video games]]. The album should be considered a subsidiarity of the film, not the other way around; that being said, ''I'm Breathless'' should still be a topic. [[User:Idiosincrático|Idiosincrático]] ([[User talk:Idiosincrático|talk]]) 02:13, 1 May 2024 (UTC)
** Duly noted. Removing ''Dick Tracy'', albeit if others disagree with you I'll bring it back. [[User:Igordebraga|igordebraga]] [[User_talk:Igordebraga|≠]] 16:25, 3 May 2024 (UTC)
***'''Support''' – [[User:Idiosincrático|Idiosincrático]] ([[User talk:Idiosincrático|talk]]) 18:32, 5 June 2024 (UTC)
*'''Comment''' Hi. I'm Breathless' impact section might need a second look. I added it, but I'm not that good in grammar. Thanks. --[[User:Apoxyomenus|Apoxyomenus]] ([[User talk:Apoxyomenus|talk]]) 03:17, 8 May 2024 (UTC)
*'''Support''' - now that the topic has been simplified per Idiosincrático's suggestion. [[User:Pseud 14|Pseud 14]] ([[User talk:Pseud 14|talk]]) 17:19, 20 May 2024 (UTC)
* {{User:NovemBot/Promote|done=yes}} '''<span style="font-family:Lucida;">[[User:Aza24|<span style="color:darkred">Aza24</span>]][[User talk:Aza24|<span style="color:#848484"> (talk)</span>]]</span>''' 00:40, 10 June 2024 (UTC)
* {{Done}}. Promotion completed successfully. Don't forget to add <code><nowiki>{{Wikipedia:Featured topics/I'm Breathless}}</nowiki></code> to the appropriate section of [[Wikipedia:Good topics]]. ~~~~
{{Archive bottom}}
WIKICODE;
		$this->assertSame( $expected, $result );
	}

	public function test_splitWikicodeIntoWikicodeAndCategories_noCategories() {
		$wikicode =
'Test
Test';
		$result = $this->p->splitWikicodeIntoWikicodeAndCategories( $wikicode );
		$expected = [
			'wikicodeTop' =>
'Test
Test',
			'wikicodeBottom' => '',
		];
		$this->assertSame( $expected, $result );
	}

	public function test_splitWikicodeIntoWikicodeAndCategories_noWikicode() {
		$wikicode =
'[[Category:Test]]';
		$result = $this->p->splitWikicodeIntoWikicodeAndCategories( $wikicode );
		$expected = [
			'wikicodeTop' => '',
			'wikicodeBottom' => '[[Category:Test]]',
		];
		$this->assertSame( $expected, $result );
	}

	public function test_splitWikicodeIntoWikicodeAndCategories_both() {
		$wikicode =
'Bob
[[Category:Test]]';
		$result = $this->p->splitWikicodeIntoWikicodeAndCategories( $wikicode );
		$expected = [
			'wikicodeTop' => 'Bob',
			'wikicodeBottom' => '[[Category:Test]]',
		];
		$this->assertSame( $expected, $result );
	}

	public function test_splitWikicodeIntoWikicodeAndCategories_bothAndLong() {
		$wikicode =
'Bob
Jill
<noinclude>
[[Category:Test]]
</noinclude>';
		$result = $this->p->splitWikicodeIntoWikicodeAndCategories( $wikicode );
		$expected = [
			'wikicodeTop' =>
'Bob
Jill',
			'wikicodeBottom' =>
'<noinclude>
[[Category:Test]]
</noinclude>',
		];
		$this->assertSame( $expected, $result );
	}

	public function test_splitWikicodeIntoWikicodeAndCategories_bothAndExtraLineBreaks() {
		$wikicode =
'Bob
Jill


<noinclude>
[[Category:Test]]
</noinclude>

';
		$result = $this->p->splitWikicodeIntoWikicodeAndCategories( $wikicode );
		$expected = [
			'wikicodeTop' =>
'Bob
Jill',
			'wikicodeBottom' =>
'

<noinclude>
[[Category:Test]]
</noinclude>

',
		];
		$this->assertSame( $expected, $result );
	}

	public function test_removeGTCFTCTemplate_top() {
		$talkPageWikicode =
'{{FTC|Overview of Ben&Ben|1}}
{{Talk header}}
';
		$topicTitle = 'Overview of Ben&Ben';
		$result = $this->p->removeGTCFTCTemplate( $talkPageWikicode, $topicTitle );
		$expected =
'{{Talk header}}
';
		$this->assertSame( $expected, $result );
	}

	public function test_removeGTCFTCTemplate_middle() {
		$talkPageWikicode =
'{{Talk header}}
{{FTC|Overview of Ben&Ben|1}}
{{Talk header}}
';
		$topicTitle = 'Overview of Ben&Ben';
		$result = $this->p->removeGTCFTCTemplate( $talkPageWikicode, $topicTitle );
		$expected =
'{{Talk header}}
{{Talk header}}
';
		$this->assertSame( $expected, $result );
	}

	public function test_removeGTCFTCTemplate_lowercase() {
		$talkPageWikicode =
'{{ftc|Overview of Ben&Ben|1}}
{{Talk header}}
';
		$topicTitle = 'Overview of Ben&Ben';
		$result = $this->p->removeGTCFTCTemplate( $talkPageWikicode, $topicTitle );
		$expected =
'{{Talk header}}
';
		$this->assertSame( $expected, $result );
	}

	public function test_removeGTCFTCTemplate_mainArticle() {
		$talkPageWikicode =
'{{FTCmain|Overview of Ben&Ben|1}}
{{Talk header}}
';
		$topicTitle = 'Overview of Ben&Ben';
		$result = $this->p->removeGTCFTCTemplate( $talkPageWikicode, $topicTitle );
		$expected =
'{{Talk header}}
';
		$this->assertSame( $expected, $result );
	}

	public function test_removeGTCFTCTemplate_twoTopics() {
		$talkPageWikicode =
'{{Talk header|archive_age=30|archive_units=days|archive_bot=Lowercase sigmabot III}}
{{GTC|Marvel Cinematic Universe Phase One films|1}}
{{GTC|Avengers films|1}}
{{FAQ|collapsed=no}}
';
		$topicTitle = 'Avengers films';
		$result = $this->p->removeGTCFTCTemplate( $talkPageWikicode, $topicTitle );
		$expected =
'{{Talk header|archive_age=30|archive_units=days|archive_bot=Lowercase sigmabot III}}
{{GTC|Marvel Cinematic Universe Phase One films|1}}
{{FAQ|collapsed=no}}
';
		$this->assertSame( $expected, $result );
	}

	public function test_removeGTCFTCTemplate_none() {
		$talkPageWikicode =
'{{Talk header|archive_age=30|archive_units=days|archive_bot=Lowercase sigmabot III}}
{{FAQ|collapsed=no}}
';
		$topicTitle = 'Avengers films';
		$result = $this->p->removeGTCFTCTemplate( $talkPageWikicode, $topicTitle );
		$expected =
'{{Talk header|archive_age=30|archive_units=days|archive_bot=Lowercase sigmabot III}}
{{FAQ|collapsed=no}}
';
		$this->assertSame( $expected, $result );
	}

	public function test_removeGTCFTCTemplate_whitespaceInTemplate() {
		$talkPageWikicode =
'{{Talk header|archive_age=30|archive_units=days|archive_bot=Lowercase sigmabot III}}
{{GTC | Avengers films	|	1}}
{{FAQ|collapsed=no}}
';
		$topicTitle = 'Avengers films';
		$result = $this->p->removeGTCFTCTemplate( $talkPageWikicode, $topicTitle );
		$expected =
'{{Talk header|archive_age=30|archive_units=days|archive_bot=Lowercase sigmabot III}}
{{FAQ|collapsed=no}}
';
		$this->assertSame( $expected, $result );
	}
}
