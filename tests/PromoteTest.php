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
}