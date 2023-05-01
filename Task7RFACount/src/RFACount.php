<?php

function countRFAs($wikicode) {
	preg_match_all('/\{\{Wikipedia:Requests for adminship\/[^\}]+\}\}/i', $wikicode, $matches);

	// don't count {{Wikipedia:Requests for adminship/Header}} and {{Wikipedia:Requests for adminship/bureaucratship}}
	$count = count($matches[0]) - 2;

	// if we get an impossible count, just return zero
	if ( $count < 0 ) {
		$count = 0;
	}

	return $count;
}

function countRFBs($wikicode) {
	preg_match_all('/\{\{Wikipedia:Requests for bureaucratship\/[^\}]+\}\}/i', $wikicode, $matches);

	$count = count($matches[0]);

	return $count;
}
