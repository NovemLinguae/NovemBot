<?php

/** Wrapper for I/O. Makes it easier to test. botclasses.php getpage and edit methods can be replaced with other code when testing. For example, instead of writing to Wikipedia, I can change edit to write to a log file. */
class WikiAPIWrapper {
	protected $eh;
	protected $READ_ONLY_TEST_MODE;
	protected $SECONDS_BETWEEN_API_READS;
	protected $SECONDS_BETWEEN_API_EDITS;
	protected $h;
	protected $wapi;
	protected $editCount;
	protected $mostRecentRevisionTimestamp;
	protected $getMostRecentRevisionTimestamp;

	function __construct(
		string $wiki_username,
		string $wiki_password,
		EchoHelper $eh,
		bool $READ_ONLY_TEST_MODE,
		int $SECONDS_BETWEEN_API_READS,
		int $SECONDS_BETWEEN_API_EDITS,
		Helper $h
	) {
		$this->eh = $eh;
		$this->READ_ONLY_TEST_MODE = $READ_ONLY_TEST_MODE;
		$this->SECONDS_BETWEEN_API_READS = $SECONDS_BETWEEN_API_READS;
		$this->SECONDS_BETWEEN_API_EDITS = $SECONDS_BETWEEN_API_EDITS;
		$this->h = $h;
		
		$this->eh->echoAndFlush("Log in", 'api_read');
		$this->wapi = new wikipedia();
		$this->wapi->beQuiet();
		$this->wapi->http->useragent = '[[en:User:NovemBot]], owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
		$this->wapi->login($wiki_username, $wiki_password);

		$this->editCount = 0;
		$this->mostRecentRevisionTimestamp = null;
	}

	function setReadOnlyMode($READ_ONLY_TEST_MODE) {
		$this->READ_ONLY_TEST_MODE = $READ_ONLY_TEST_MODE;
	}

	function getpage(string $namespace_and_title) {
		$output = $this->wapi->getpage($namespace_and_title);
		$message = "Read data from page: $namespace_and_title";
		$message .= "\n\n$output";
		$this->eh->echoAndFlush($message, 'api_read');
		sleep($this->SECONDS_BETWEEN_API_READS);
		return $output;
	}
	
	/** must include Category: in category name */
	/*
	function categorymembers($category) {
		$output = $this->wapi->categorymembers($category);
		$message = "Get members of category: $category";
		$message .= "\n\n" . var_export($output, true);
		$this->eh->echoAndFlush($message, 'api_read');
		return $output;
	}
	*/

	function getUnreadPings() {
		$output = $this->wapi->query('?action=query&format=json&meta=notifications&notfilter=!read');
		$message = "Getting list of pings";
		$message .= "\n\n" . var_export($output, true);
		$this->eh->echoAndFlush($message, 'api_read');
		return $output;
	}

	function markAllPingsRead() {
		$csrfToken = $this->wapi->query('?action=query&format=json&meta=tokens');
		$csrfToken = $csrfToken['query']['tokens']['csrftoken'];
		$output = $this->wapi->query("?action=echomarkread&format=json&all=1", ['token' => $csrfToken]);
		$message = "Marking all pings read";
		$message .= "\n\n" . var_export($output, true);
		$this->eh->echoAndFlush($message, 'api_read');
	}

	// TODO: does page title need underscores?
	function edit(string $namespace_and_title, string $wikicode, string $topicPageTitle, string $goodOrFeatured): void {
		$editSummary = "promote [[$topicPageTitle]] to $goodOrFeatured topic (NovemBot Task 1)";
		$message = 'Write data to page:<br /><input type="text" value="' . htmlspecialchars($namespace_and_title) . '" />';
		$message .= "<br />Wikitext:<br /><textarea>" . htmlspecialchars($wikicode) . "</textarea>";
		$message .= "<br />" . 'Edit summary:<br /><input type="text" value="' . htmlspecialchars($editSummary) . '" />';
		$this->eh->echoAndFlush($message, 'api_write');
		//echoAndFlush($READ_ONLY_TEST_MODE, 'variable');
		if ( ! $this->READ_ONLY_TEST_MODE ) {
			$response = $this->wapi->edit(
				$namespace_and_title,
				$wikicode,
				$editSummary
			);
			$this->mostRecentRevisionTimestamp = $response['edit']['newtimestamp'];
			$this->editCount++;
			sleep($this->SECONDS_BETWEEN_API_EDITS);
		}
	}
	
	// TODO: does page title need underscores?
	function editSimple(string $namespace_and_title, string $wikicode, string $editSummary): void {
		$message = 'Write data to page:<br /><input type="text" value="' . htmlspecialchars($namespace_and_title) . '" />';
		$message .= "<br />Wikitext:<br /><textarea>" . htmlspecialchars($wikicode) . "</textarea>";
		$message .= "<br />" . 'Edit summary:<br /><input type="text" value="' . htmlspecialchars($editSummary) . '" />';
		$this->eh->echoAndFlush($message, 'api_write');
		//echoAndFlush($READ_ONLY_TEST_MODE, 'variable');
		if ( ! $this->READ_ONLY_TEST_MODE ) {
			$response = $this->wapi->edit(
				$namespace_and_title,
				$wikicode,
				$editSummary
			);
			$this->mostRecentRevisionTimestamp = $response['edit']['newtimestamp'];
			$this->editCount++;
			sleep($this->SECONDS_BETWEEN_API_EDITS);
		}
	}

	function getRevisionIDOfMostRecentRevision($pageTitle) {
		$parameters = [
			"action" => "query",
			"format" => "json",
			"prop" => "revisions",
			"titles" => $pageTitle,
			"formatversion" => "2",
			"rvlimit" => "1",
			"rvdir" => "older"
		];

		$output = $this->query($parameters);
		$output = $output['query']['pages'][0]['revisions'][0]['revid'];

		$message = "Getting revision ID of latest revision";
		$message .= "\n\n" . var_export($output, true);
		$this->eh->echoAndFlush($message, 'api_read');

		return $output;
	}

	function query(Array $parameters) {
		// urlencode() everything, and start building the $_GET[] string
		array_walk($parameters, function(&$value, $key) {
			$value = $key . '=' . urlencode($value);
		});

		// finish building the $_GET[] string
		$string = '?' . implode('&', $parameters);

		$output = $this->wapi->query($string);

		return $output;
	}

	function getEditCount() {
		return $this->editCount;
	}

	function setEditCountToZero() {
		$this->editCount = 0;
	}

	function getMostRecentRevisionTimestamp() {
		return $this->h->convertTimestampToOffsetFormat($this->getMostRecentRevisionTimestamp);
	}
}