<?php

class RFACount {
	public function doRFA( $wapi, $rfaPageWikitext ) {
		$count = $this->countRFAs( $rfaPageWikitext );

		$wikicodeToWrite =
"$count<noinclude>
{{Documentation}}
</noinclude>";
		$editSummary = "set RFA count to $count (NovemBot Task 7)";
		$wapi->edit( 'User:Amalthea/RfX/RfA count', $wikicodeToWrite, $editSummary );
	}

	public function doRFB( $wapi, $rfaPageWikitext ) {
		$count = $this->countRFBs( $rfaPageWikitext );

		$wikicodeToWrite = $count;
		$editSummary = "set RFB count to $count (NovemBot Task 7)";
		$wapi->edit( 'User:Amalthea/RfX/RfB count', $wikicodeToWrite, $editSummary );
	}

	public function countRFAs( $wikicode ) {
		preg_match_all(
			'/\{\{\s*Wikipedia:Requests for adminship\/([^\}\|]+)(?:\|[^\}]*)?\}\}/i',
			$wikicode,
			$matches
		);
		return count( $matches[1] );
	}

	public function countRFBs( $wikicode ) {
		preg_match_all( '/\{\{Wikipedia:Requests for bureaucratship\/[^\}]+\}\}/i', $wikicode, $matches );

		$count = count( $matches[0] );

		return $count;
	}
}
