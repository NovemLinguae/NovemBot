<?php

/** Wrapper for I/O. Makes it easier to test. botclasses.php getpage and edit methods can be replaced with other code when testing. For example, instead of writing to Wikipedia, I can change edit to write to a log file. */
class WikiAPIWrapper {
	function __construct(string $wiki_username, string $wiki_password) {
		echoAndFlush("Log in", 'api_read');
		$this->objwiki = new wikipedia();
		$this->objwiki->beQuiet();
		$this->objwiki->http->useragent = '[[en:User:NovemBot]], owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
		$this->objwiki->login($wiki_username, $wiki_password);
	}

	function getpage(string $namespace_and_title) {
		$output = $this->objwiki->getpage($namespace_and_title);
		$message = "Read data from page: $namespace_and_title";
		$message .= "\n\n$output";
		echoAndFlush($message, 'api_read');
		return $output;
	}
	
	/** must include Category: in category name */
	function categorymembers($category) {
		$output = $this->objwiki->categorymembers($category);
		$message = "Get members of category: $category";
		$message .= "\n\n" . var_export($output, true);
		echoAndFlush($message, 'api_read');
		return $output;
	}
	
	// TODO: does page title need underscores?
	function edit(string $namespace_and_title, string $wikicode, string $edit_summary = 'NovemBot Task 1: promote successful featured topic/good topic candidate'): void {
		global $READ_ONLY_TEST_MODE, $SECONDS_BETWEEN_API_EDITS;
		$message = "Write data to page: $namespace_and_title";
		$message .= "\n\n$wikicode";
		echoAndFlush($message, 'api_write');
		//echoAndFlush($READ_ONLY_TEST_MODE, 'variable');
		if ( ! $READ_ONLY_TEST_MODE ) {
			$this->objwiki->edit(
				$namespace_and_title,
				$wikicode,
				$edit_summary
			);
			sleep($SECONDS_BETWEEN_API_EDITS);
		}
	}
}