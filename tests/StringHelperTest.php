<?php

use PHPUnit\Framework\TestCase;

class StringHelperTest extends TestCase {
	function setUp(): void {
		$this->sh = new StringHelper();
	}

	function test_insertCodeAtEndOfFirstTemplate_TemplateWithParameters() {
		$templateNameRegEx = 'Article ?history';
		$codeToInsert = '[inserted code]';
		$wikicode = '{{Article history|test}}';
		$result = $this->sh->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
		$this->assertSame(
'{{Article history|test
[inserted code]
}}'
		, $result);
	}
	
	function test_insertCodeAtEndOfFirstTemplate_CaseInsensitiveIsWorking() {
		$templateNameRegEx = 'Article ?history';
		$codeToInsert = '[inserted code]';
		$wikicode = '{{article history|test}}';
		$result = $this->sh->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
		$this->assertSame(
'{{article history|test
[inserted code]
}}'
		, $result);
	}
	
	function test_insertCodeAtEndOfFirstTemplate_RegExIsWorking() {
		$templateNameRegEx = 'Article ?history';
		$codeToInsert = '[inserted code]';
		$wikicode = '{{Articlehistory|test}}';
		$result = $this->sh->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
		$this->assertSame(
'{{Articlehistory|test
[inserted code]
}}'
		, $result);
	}
		
	function test_insertCodeAtEndOfFirstTemplate_TemplateWithNestedTemplate() {
		$templateNameRegEx = 'Article ?history';
		$codeToInsert = '[inserted code]';
		$wikicode = '{{Article history|{{Nested template}}test}}';
		$result = $this->sh->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
		$this->assertSame(
'{{Article history|{{Nested template}}test
[inserted code]
}}'
		, $result);
	}
	
	// In wikicode, not allowed to have a {{template name}} split across multiple lines, so no need to test that.
	
	function test_insertCodeAtEndOfFirstTemplate_TemplateWithNoParameters() {
		$templateNameRegEx = 'Article ?history';
		$codeToInsert = '[inserted code]';
		$wikicode = '{{Article history}}';
		$result = $this->sh->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
		$this->assertSame(
'{{Article history
[inserted code]
}}'
		, $result);
	}
	
	function test_insertCodeAtEndOfFirstTemplate_TwoTemplatesWithSameName() {
		$templateNameRegEx = 'Article ?history';
		$codeToInsert = '[inserted code]';
		$wikicode = 
'test
{{Article history|parameter}}
test
{{Article history|parameter}}
test';
		$result = $this->sh->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
		$this->assertSame(
'test
{{Article history|parameter
[inserted code]
}}
test
{{Article history|parameter}}
test'
		, $result);
	}
	
	function test_preg_position_false() {
		$regex = '/hello/si';
		$haystack = 'How are you?';
		$result = $this->sh->preg_position($regex, $haystack);
		$this->assertFalse($result);
	}
	
	function test_preg_position_zero() {
		$regex = '/How/si';
		$haystack = 'How are you?';
		$result = $this->sh->preg_position($regex, $haystack);
		$this->assertSame(0, $result);
	}
	
	function test_preg_position_positive() {
		$regex = '/are/si';
		$haystack = 'How are you?';
		$result = $this->sh->preg_position($regex, $haystack);
		$this->assertSame(4, $result);
	}
	
	function test_preg_position_end() {
		$regex = '/$/si';
		$haystack = 'How are you?';
		$result = $this->sh->preg_position($regex, $haystack);
		$this->assertSame(12, $result);
	}
	
	/*
	function test_sliceFirstHTMLTagFound_start() {
		$wikicode = '<noinclude>Test</noinclude> Test';
		$tagWithNoLTGT = 'noinclude';
		$result = $this->sh->sliceFirstHTMLTagFound($wikicode, $tagWithNoLTGT);
		$this->assertSame('<noinclude>Test</noinclude>', $result);
	}
	
	function test_sliceFirstHTMLTagFound_middle() {
		$wikicode = 'Test <noinclude>Test</noinclude> Test';
		$tagWithNoLTGT = 'noinclude';
		$result = $this->sh->sliceFirstHTMLTagFound($wikicode, $tagWithNoLTGT);
		$this->assertSame('<noinclude>Test</noinclude>', $result);
	}
	
	function test_sliceFirstHTMLTagFound_end() {
		$wikicode = 'Test <noinclude>Test</noinclude>';
		$tagWithNoLTGT = 'noinclude';
		$result = $this->sh->sliceFirstHTMLTagFound($wikicode, $tagWithNoLTGT);
		$this->assertSame('<noinclude>Test</noinclude>', $result);
	}
	
	function test_sliceFirstHTMLTagFound_twoTags() {
		$wikicode = 'Test <othertag>Hello</othertag> <noinclude>Test</noinclude> Test';
		$tagWithNoLTGT = 'noinclude';
		$result = $this->sh->sliceFirstHTMLTagFound($wikicode, $tagWithNoLTGT);
		$this->assertSame('<noinclude>Test</noinclude>', $result);
	}
	
	function test_sliceFirstHTMLTagFound_regexCharactersThatNeedEscaping() {
		$wikicode = 'Test <noinc*lude>Test</noinc*lude>';
		$tagWithNoLTGT = 'noinc*lude';
		$result = $this->sh->sliceFirstHTMLTagFound($wikicode, $tagWithNoLTGT);
		$this->assertSame('<noinc*lude>Test</noinc*lude>', $result);
	}
	
	function test_sliceFirstHTMLTagFound_notFound() {
		$wikicode = 'Test <noinclude>Test</noinclude> Test';
		$tagWithNoLTGT = 'fakeTag';
		$this->expectException(InvalidArgumentException::class);
		$this->sh->sliceFirstHTMLTagFound($wikicode, $tagWithNoLTGT);
	}
	*/
}