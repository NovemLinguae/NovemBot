<?php

/** Wrapper for I/O. Makes it easier to test. botclasses.php getpage and edit methods can be replaced with other code when testing. For example, instead of writing to Wikipedia, I can change edit to write to a log file. */
class WikiAPIWrapper {
	function __construct(string $wiki_username, string $wiki_password, EchoHelper $eh) {
		$this->eh = $eh;
		
		$this->eh->echoAndFlush("Log in", 'api_read');
		$this->wapi = new wikipedia();
		$this->wapi->beQuiet();
		$this->wapi->http->useragent = '[[en:User:NovemBot]], owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
		$this->wapi->login($wiki_username, $wiki_password);
	}

	function getpage(string $namespace_and_title) {
		global $SECONDS_BETWEEN_API_READS;
		$output = $this->wapi->getpage($namespace_and_title);
		$message = "Read data from page: $namespace_and_title";
		$message .= "\n\n$output";
		$this->eh->echoAndFlush($message, 'api_read');
		sleep($SECONDS_BETWEEN_API_READS);
		return $output;
	}
	
	/** must include Category: in category name */
	function categorymembers($category) {
		$output = $this->wapi->categorymembers($category);
		$message = "Get members of category: $category";
		$message .= "\n\n" . var_export($output, true);
		$this->eh->echoAndFlush($message, 'api_read');
		return $output;
	}
	
	// TODO: does page title need underscores?
	function edit(string $namespace_and_title, string $wikicode, string $topicPageTitle, string $goodOrFeatured): void {
		global $READ_ONLY_TEST_MODE, $SECONDS_BETWEEN_API_EDITS;
		$editSummary = "promote [[$topicPageTitle]] to $goodOrFeatured topic";
		$message = 'Write data to page:<br /><input type="text" value="' . htmlspecialchars($namespace_and_title) . '" />';
		$message .= "<br />Wikitext:<br /><textarea>" . htmlspecialchars($wikicode) . "</textarea>";
		$message .= "<br />" . 'Edit summary:<br /><input type="text" value="' . htmlspecialchars($editSummary) . '" />';
		$this->eh->echoAndFlush($message, 'api_write');
		//echoAndFlush($READ_ONLY_TEST_MODE, 'variable');
		if ( ! $READ_ONLY_TEST_MODE ) {
			$this->wapi->edit(
				$namespace_and_title,
				$wikicode,
				$editSummary
			);
			sleep($SECONDS_BETWEEN_API_EDITS);
		}
	}
	
	// TODO: does page title need underscores?
	function editSimple(string $namespace_and_title, string $wikicode, string $editSummary): void {
		global $READ_ONLY_TEST_MODE, $SECONDS_BETWEEN_API_EDITS;
		$message = 'Write data to page:<br /><input type="text" value="' . htmlspecialchars($namespace_and_title) . '" />';
		$message .= "<br />Wikitext:<br /><textarea>" . htmlspecialchars($wikicode) . "</textarea>";
		$message .= "<br />" . 'Edit summary:<br /><input type="text" value="' . htmlspecialchars($editSummary) . '" />';
		$this->eh->echoAndFlush($message, 'api_write');
		//echoAndFlush($READ_ONLY_TEST_MODE, 'variable');
		if ( ! $READ_ONLY_TEST_MODE ) {
			$this->wapi->edit(
				$namespace_and_title,
				$wikicode,
				$editSummary
			);
			sleep($SECONDS_BETWEEN_API_EDITS);
		}
	}
}