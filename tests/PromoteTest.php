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
	
	function test_setTemplateBoxTemplateViewParamterToYes_inputContainsViewYes() {
		$topicBoxWikicode = '{{Featured topic box|view=yes}}';
		$result = setTemplateBoxTemplateViewParamterToYes($topicBoxWikicode);
		$this->assertSame('{{Featured topic box|view=yes}}', $result);
	}
	
	function test_setTemplateBoxTemplateViewParamterToYes_inputContainsViewYes2() {
		$topicBoxWikicode = '{{Featured topic box | view = yes }}';
		$result = setTemplateBoxTemplateViewParamterToYes($topicBoxWikicode);
		$this->assertSame('{{Featured topic box | view = yes }}', $result);
	}
	
	function test_setTemplateBoxTemplateViewParamterToYes_inputContainsViewYes3() {
		$topicBoxWikicode =
'{{Featured topic box
| view = yes
}}';
		$result = setTemplateBoxTemplateViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
| view = yes
}}'
		, $result);
	}
	
	function test_setTemplateBoxTemplateViewParamterToYes_inputContainsViewNo1() {
		$topicBoxWikicode = '{{Featured topic box|view=no}}';
		$result = setTemplateBoxTemplateViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTemplateBoxTemplateViewParamterToYes_inputContainsViewNo2() {
		$topicBoxWikicode =
'{{Featured topic box
| view = no
}}';
		$result = setTemplateBoxTemplateViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTemplateBoxTemplateViewParamterToYes_inputIsJustTemplateName1() {
		$topicBoxWikicode = '{{Featured topic box}}';
		$result = setTemplateBoxTemplateViewParamterToYes($topicBoxWikicode);
		$this->assertSame(
'{{Featured topic box
|view=yes
}}'
		, $result);
	}
	
	function test_setTemplateBoxTemplateViewParamterToYes_inputIsJustTemplateName2() {
		$topicBoxWikicode =
'{{Featured topic box

}}';
		$result = setTemplateBoxTemplateViewParamterToYes($topicBoxWikicode);
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
}