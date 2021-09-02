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
	
	function test_arrayDeleteValue_deleteOneValue() {
		$array = ['test1', 'test2', 'test3'];
		$valueToDelete = 'test2';
		$result = $this->sh->arrayDeleteValue($array, $valueToDelete);
		$this->assertSame(['test1', 'test3'], $result);
	}
	
	function test_arrayDeleteValue_okIfValueNotFound() {
		$array = ['test1', 'test2', 'test3'];
		$valueToDelete = 'test4';
		$result = $this->sh->arrayDeleteValue($array, $valueToDelete);
		$this->assertSame(['test1', 'test2', 'test3'], $result);
	}
	
	function test_arrayDeleteValue_firstParameterNotArray() {
		$array = 'test1';
		$valueToDelete = 'test4';
		$this->expectException(InvalidArgumentException::class);
		$this->sh->arrayDeleteValue($array, $valueToDelete);
	}
}