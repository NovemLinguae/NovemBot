<?php

use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase {
	function setUp(): void {
		$this->h = new Helper();
	}

	function test_insertCodeAtEndOfFirstTemplate_TemplateWithParameters() {
		$templateNameRegEx = 'Article ?history';
		$codeToInsert = '[inserted code]';
		$wikicode = '{{Article history|test}}';
		$result = $this->h->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = $this->h->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = $this->h->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = $this->h->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = $this->h->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = $this->h->insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegEx, $codeToInsert);
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
		$result = $this->h->preg_position($regex, $haystack);
		$this->assertFalse($result);
	}
	
	function test_preg_position_zero() {
		$regex = '/How/si';
		$haystack = 'How are you?';
		$result = $this->h->preg_position($regex, $haystack);
		$this->assertSame(0, $result);
	}
	
	function test_preg_position_positive() {
		$regex = '/are/si';
		$haystack = 'How are you?';
		$result = $this->h->preg_position($regex, $haystack);
		$this->assertSame(4, $result);
	}
	
	function test_preg_position_end() {
		$regex = '/$/si';
		$haystack = 'How are you?';
		$result = $this->h->preg_position($regex, $haystack);
		$this->assertSame(12, $result);
	}
	
	function test_deleteArrayValue_deleteOneValue() {
		$array = ['test1', 'test2', 'test3'];
		$valueToDelete = 'test2';
		$result = $this->h->deleteArrayValue($array, $valueToDelete);
		$this->assertSame(['test1', 'test3'], $result);
	}
	
	function test_deleteArrayValue_okIfValueNotFound() {
		$array = ['test1', 'test2', 'test3'];
		$valueToDelete = 'test4';
		$result = $this->h->deleteArrayValue($array, $valueToDelete);
		$this->assertSame(['test1', 'test2', 'test3'], $result);
	}
	
	function test_deleteArrayValue_firstParameterNotArray() {
		$array = 'test1';
		$valueToDelete = 'test4';
		$this->expectException(TypeError::class);
		$this->h->deleteArrayValue($array, $valueToDelete);
	}
	
	function test_deleteMiddleOfString() {
		$string = 'Test DELETE THIS dont delete this';
		$deleteStartPosition = 5;
		$deleteEndPosition = 17;
		$result = $this->h->deleteMiddleOfString($string, $deleteStartPosition, $deleteEndPosition);
		$this->assertSame('Test dont delete this', $result);
	}
	
	function test_deleteMiddleOfString_blank() {
		$string = '';
		$deleteStartPosition = 0;
		$deleteEndPosition = 0;
		$result = $this->h->deleteMiddleOfString($string, $deleteStartPosition, $deleteEndPosition);
		$this->assertSame('', $result);
	}
}