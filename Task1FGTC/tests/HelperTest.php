<?php

use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase {
	function setUp(): void {
		$this->h = new Helper();
	}

	// TODO: add @group for grouping. this is equivlent to Jest's "describe"

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
	
	function test_deleteArrayValuesBeginningWith_normal() {
		$array = [
			1 => 'a',
			2 => 'b',
			3 => 'c',
			4 => '1',
			5 => '2',
			6 => '3',
			7 => '31',
			8 => '4',
			9 => '',
			10 => true,
			11 => false
		];
		$prefix = '3';
		$result = $this->h->deleteArrayValuesBeginningWith($array, $prefix);
		$expected = [
			1 => 'a',
			2 => 'b',
			3 => 'c',
			4 => '1',
			5 => '2',
			8 => '4',
			9 => '',
			10 => true,
			11 => false
		];
		$this->assertSame($expected, $result);
	}

	function test_sliceFirstTemplateFound_normal() {
		$wikicode = 
"Test
{{Good topic box
| algo                = old(120d)
| archive             = Wikipedia talk:Featured and good topic candidates/%(year)d
| archiveheader       = {{Automatic archive navigator}}
| minthreadstoarchive = 1
| minthreadsleft      = 4
}}
{{tmbox
|text= '''Questions about a topic you are working on or about the process in general should be asked at [[Wikipedia talk:Featured and good topic questions|Featured and good topic questions]].'''  This page is primarily for discussion on proposals regarding the FTC process.
}}";
		$templateName = 'good topic box';
		$result = $this->h->sliceFirstTemplateFound($wikicode, $templateName);
		$expected =
"{{Good topic box
| algo                = old(120d)
| archive             = Wikipedia talk:Featured and good topic candidates/%(year)d
| archiveheader       = {{Automatic archive navigator}}
| minthreadstoarchive = 1
| minthreadsleft      = 4
}}";
		$this->assertSame($expected, $result);
	}

	function test_sliceFirstTemplateFound_secondTemplate() {
		$wikicode = 
"Test
{{tmbox
|text= '''Questions about a topic you are working on or about the process in general should be asked at [[Wikipedia talk:Featured and good topic questions|Featured and good topic questions]].'''  This page is primarily for discussion on proposals regarding the FTC process.
}}
{{Good topic box
| algo                = old(120d)
| archive             = Wikipedia talk:Featured and good topic candidates/%(year)d
| archiveheader       = {{Automatic archive navigator}}
| minthreadstoarchive = 1
| minthreadsleft      = 4
}}";
		$templateName = 'good topic box';
		$result = $this->h->sliceFirstTemplateFound($wikicode, $templateName);
		$expected =
"{{Good topic box
| algo                = old(120d)
| archive             = Wikipedia talk:Featured and good topic candidates/%(year)d
| archiveheader       = {{Automatic archive navigator}}
| minthreadstoarchive = 1
| minthreadsleft      = 4
}}";
		$this->assertSame($expected, $result);
	}

	function test_sliceFirstTemplateFound_templateNotFound() {
		$wikicode = 
"{{User:MiszaBot/config
| algo                = old(120d)
| archive             = Wikipedia talk:Featured and good topic candidates/%(year)d
| archiveheader       = {{Automatic archive navigator}}
| minthreadstoarchive = 1
| minthreadsleft      = 4
}}
{{tmbox
|text= '''Questions about a topic you are working on or about the process in general should be asked at [[Wikipedia talk:Featured and good topic questions|Featured and good topic questions]].'''  This page is primarily for discussion on proposals regarding the FTC process.
}}";
		$templateName = 'good topic box';
		$result = $this->h->sliceFirstTemplateFound($wikicode, $templateName);
		$expected = null;
		$this->assertSame($expected, $result);
	}

	function test_convertTimestampToOffsetFormat_normal() {
		$string = '2022-11-25T12:05:26Z';
		$result = $this->h->convertTimestampToOffsetFormat($string);
		$this->assertSame('20221125120526', $result);
	}
}