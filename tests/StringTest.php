<?php

use PHPUnit\Framework\TestCase;

class StringTest extends TestCase {
	function test_insertCodeAtEndOfFirstTemplate_TemplateWithParameters() {
		$templateNameRegEx = 'Article ?history';
		$codeToInsert = '[inserted code]';
		$wikicode = '{{Article history|test}}';
		$result = insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
}