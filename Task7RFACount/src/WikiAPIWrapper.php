<?php

/**
 * Wrapper for I/O. Makes it easier to test. botclasses.php getpage and edit methods can be replaced with other code when testing. For example, instead of writing to Wikipedia, I can change edit to write to a log file.
 */
class WikiAPIWrapper {
	public function __construct( string $wiki_username, string $wiki_password, EchoHelper $eh ) {
		$this->eh = $eh;

		$this->eh->echoAndFlush( "Log in", 'api_read' );
		$this->wapi = new wikipedia();
		$this->wapi->beQuiet();
		$this->wapi->http->useragent = '[[en:User:NovemBot]], owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
		$this->wapi->login( $wiki_username, $wiki_password );
	}

	public function getpage( string $namespace_and_title ) {
		global $SECONDS_BETWEEN_API_READS;
		$output = $this->wapi->getpage( $namespace_and_title );
		$message = "Read data from page: $namespace_and_title";
		$message .= "\n\n$output";
		$this->eh->echoAndFlush( $message, 'api_read' );
		sleep( $SECONDS_BETWEEN_API_READS );
		return $output;
	}

	public function getUnreadPings() {
		$output = $this->wapi->query( '?action=query&format=json&meta=notifications&notfilter=!read' );
		$message = "Getting list of pings";
		$message .= "\n\n" . var_export( $output, true );
		$this->eh->echoAndFlush( $message, 'api_read' );
		return $output;
	}

	public function markAllPingsRead() {
		$csrfToken = $this->wapi->query( '?action=query&format=json&meta=tokens' );
		$csrfToken = $csrfToken['query']['tokens']['csrftoken'];
		$output = $this->wapi->query( "?action=echomarkread&format=json&all=1", [ 'token' => $csrfToken ] );
		$message = "Marking all pings read";
		$message .= "\n\n" . var_export( $output, true );
		$this->eh->echoAndFlush( $message, 'api_read' );
	}

	/**
	 * @todo does page title need underscores?
	 */
	public function edit( string $namespace_and_title, string $wikicode, string $editSummary ): void {
		global $READ_ONLY_TEST_MODE, $SECONDS_BETWEEN_API_EDITS;
		$message = 'Write data to page:<br /><input type="text" value="' . htmlspecialchars( $namespace_and_title ) . '" />';
		$message .= "<br />Wikitext:<br /><textarea>" . htmlspecialchars( $wikicode ) . "</textarea>";
		$message .= "<br />" . 'Edit summary:<br /><input type="text" value="' . htmlspecialchars( $editSummary ) . '" />';
		$this->eh->echoAndFlush( $message, 'api_write' );
		// echoAndFlush($READ_ONLY_TEST_MODE, 'variable');
		if ( !$READ_ONLY_TEST_MODE ) {
			$this->wapi->edit(
				$namespace_and_title,
				$wikicode,
				$editSummary
			);
			sleep( $SECONDS_BETWEEN_API_EDITS );
		}
	}

	/**
	 * @todo does page title need underscores?
	 */
	public function editSimple( string $namespace_and_title, string $wikicode, string $editSummary ): void {
		global $READ_ONLY_TEST_MODE, $SECONDS_BETWEEN_API_EDITS;
		$message = 'Write data to page:<br /><input type="text" value="' . htmlspecialchars( $namespace_and_title ) . '" />';
		$message .= "<br />Wikitext:<br /><textarea>" . htmlspecialchars( $wikicode ) . "</textarea>";
		$message .= "<br />" . 'Edit summary:<br /><input type="text" value="' . htmlspecialchars( $editSummary ) . '" />';
		$this->eh->echoAndFlush( $message, 'api_write' );
		// echoAndFlush($READ_ONLY_TEST_MODE, 'variable');
		if ( !$READ_ONLY_TEST_MODE ) {
			$this->wapi->edit(
				$namespace_and_title,
				$wikicode,
				$editSummary
			);
			sleep( $SECONDS_BETWEEN_API_EDITS );
		}
	}
}
