<?php

class Helper {
	/** Grabs template Wikicode of first instance encountered of that template. Case insensitive. Returns null if no template found. */
	function sliceFirstTemplateFound(string $wikicode, string $templateName) {
		$starting_position = strpos(strtolower($wikicode), "{{" . strtolower($templateName));
		if ( $starting_position === false ) return null;
		$counter = 0;
		$length = strlen($wikicode);
		for ( $i = $starting_position + 2; $i < $length; $i++ ) {
			$next_two = substr($wikicode, $i, 2);
			if ( $next_two == "{{" ) {
				$counter++;
				continue;
			} elseif ( $next_two == "}}" ) {
				if ( $counter == 0 ) {
					return substr($wikicode, $starting_position, $i - $starting_position + 2);
				} else {
					$counter--;
					continue;
				}
			}
		}
		return null;
	}
	
	/** Used by echoAndFlush() */
	function nbsp($string) {
		$string = preg_replace('/\t/', '&nbsp;&nbsp;&nbsp;&nbsp;', $string);
		
		// replace more than 1 space in a row with &nbsp;
		$string = preg_replace('/  /m', '&nbsp;&nbsp;', $string);
		$string = preg_replace('/ &nbsp;/m', '&nbsp;&nbsp;', $string);
		$string = preg_replace('/&nbsp; /m', '&nbsp;&nbsp;', $string);
		
		if ( $string == ' ' ) {
			$string = '&nbsp;';
		}
		
		return $string;
	}

	/** Similar to preg_match, except always returns the contents of the first match. No need to deal with a $matches[1] variable. */
	function preg_first_match($regex, $haystack, $throwErrorIfNoMatch = false) {
		preg_match($regex, $haystack, $matches);
		if ( isset($matches[1]) ) {
			return $matches[1];
		}
		if ( $throwErrorIfNoMatch ) {
			throw new Exception("RegEx match not found in the following RegEx: $regex");
		} else {
			return '';
		}
	}
	
	function deleteLastLineOfString($string) {
		return substr($string, 0, strrpos($string, "\n"));
	}

	function insertStringIntoStringAtPosition($oldstr, $str_to_insert, $pos) {
		return substr_replace($oldstr, $str_to_insert, $pos, 0);
	}

	function insertCodeAtEndOfFirstTemplate($wikicode, $templateNameRegExNoDelimiters, $codeToInsert) {
		// This uses RegEx recursion (?2) to handle nested braces {{ }}.
		// https://regex101.com/r/GmiY1z/1
		return preg_replace('/({{' . $templateNameRegExNoDelimiters . '\s*\|?((?:(?!{{|}}).|{{(?2)}})*))(}})/is', "$1\n$codeToInsert\n$3", $wikicode, 1);
	}

	function preg_position($regex, $haystack) {
		preg_match($regex, $haystack, $matches, PREG_OFFSET_CAPTURE);
		return $matches[0][1] ?? false;
	}
	
	function deleteArrayValue(array $array, $valueToDelete) {
		$array = array_diff($array, [$valueToDelete]); // delete value
		$array = array_values($array); // reindex (fix keys), 0 to whatever
		return $array;
	}
	
	function deleteMiddleOfString($string, $deleteStartPosition, $deleteEndPosition) {
		$pos = $deleteStartPosition;
		$len = $deleteEndPosition - $deleteStartPosition;
		
		$part1 = substr($string, 0, $deleteStartPosition);
		$part2 = substr($string, $deleteEndPosition);
		
		$final_str = $part1 . $part2;
		return $final_str;
	}
	
	function deleteArrayValuesBeginningWith(array $array, string $prefix) {
		if ( $prefix === '' ) throw new InvalidArgumentException();
		
		$array2 = [];
		foreach ( $array as $key => $value ) {
			if ( ! $this->str_starts_with($value, $prefix) ) {
				$array2[$key] = $value;
			}
		}
		return $array2;
	}
	
	function str_starts_with( $haystack, $needle ) {
		return strpos( $haystack , $needle ) === 0;
	}
}