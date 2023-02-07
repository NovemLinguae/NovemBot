<?php

// https://novem-bot.toolforge.org/task-b/SuspiciousImageFinder.php?password=

// TODO: dependency inject foreign classes: wikipedia and PDO
// TODO: to solve out of memory errors, can probably just create a ToolForge webservice with more memory: webservice --mem=2g or similar

include("botclasses.php");
include("logininfo.php");

class SuspiciousImageFinder {
	protected $get;
	protected $http_get_password;
	protected $argv;
	protected $wiki_username;
	protected $wiki_password;
	protected $databaseConfigFile;
	protected $enwiki;
	protected $wikiAPI;

	public function execute($get, $http_get_password, $argv, $wiki_username, $wiki_password, $databaseConfigFile) {
		$this->get = $get;
		$this->http_get_password = $http_get_password;
		$this->argv = $argv;
		$this->wiki_username = $wiki_username;
		$this->wiki_password = $wiki_password;
		$this->databaseConfigFile = $databaseConfigFile;

		$this->setHeader();
		$this->setErrorReporting();
		$this->checkPermissions();
		$this->printPHPVersion();
		$this->connectToSQLDatabases();
		$i = 1;
		$result = true;
		$filesMatchingCriteria = '';
		while ( $result ) {
			$filesToCheck = $this->getFiles($i);
			$result = $this->checkFiles($filesToCheck);
			$this->echoAndFlush("\n\nResult: " . var_export($result, true));
			$filesMatchingCriteria .= $result;
			$i++;
		}
		$this->logInToWikipedia();
		$this->makeEdit($filesMatchingCriteria, 'User:Minorax/files');
		$this->echoAndFlush("\n\nAll done!");
	}
	
	private function setHeader() {
		header('Content-Type:text/plain; charset=utf-8; Content-Encoding: none');
	}

	private function setErrorReporting() {
		ini_set("display_errors", 1);
		error_reporting(E_ALL);
	}

	private function checkPermissions() {
		if ( ($this->get['password'] ?? '') != $this->http_get_password && ($this->argv[1] ?? '') != $this->http_get_password ) {
			die('Invalid password.');
		}
	}

	private function printPHPVersion() {
		$this->echoAndFlush("PHP version: " . PHP_VERSION);
	}

	private function echoAndFlush($str) {
		echo $str;
	}

	private function connectToSQLDatabases() {
		// Must use database_p. database will not work.
		$this->enwiki = new PDO("mysql:host=enwiki.analytics.db.svc.wikimedia.cloud;dbname=enwiki_p", $this->databaseConfigFile['user'], $this->databaseConfigFile['password']);
		$this->enwiki->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	}

	private function getFiles($i) {
		$this->echoAndFlush("\n\nGenerating list of files to check (batch $i)...");
		$limit = 30000; // due to PHP running out of memory
		$offset = ($i - 1) * $limit;
		$this->echoAndFlush("\nLimit: $limit\nOffset: $offset");
		$query = $this->enwiki->prepare("
			SELECT img_name, img_metadata
			FROM categorylinks
			JOIN page ON page_id = cl_from
			JOIN image ON img_name = page_title
			LEFT JOIN actor_image ON actor_id = img_actor
			LEFT JOIN user ON user_id = actor_user
			WHERE cl_to = 'Self-published_work'
				AND cl_from IN (
					SELECT cl_from
					FROM categorylinks
					WHERE cl_to = 'All_free_media'
				)
				AND user_name NOT IN (
					'Orange Suede Sofa',
					'Centpacrr',
					'Locke Cole',
					'Atsme',
					'Patrickroque01',
					'Xaosflux',
					'Wilderf353'
				)
			ORDER BY img_name ASC
			LIMIT $limit
			OFFSET $offset
		");
		$query->execute();
		return $query->fetchAll();
	}

	private function checkFiles($filesToCheck) {
		$this->echoAndFlush("\n\nChecking files. This may take awhile...");
		$result = '';
		foreach ( $filesToCheck as $key => $file ) {
			$fileName = $file['img_name'];

			// Strangely, img_metadata can be stored as either PHP serialized, or JSON. We need to decode one, see if that fails, then try to decode the other.
			$metaData = @unserialize($file['img_metadata']);
			if ( ! $metaData ) {
				$metaData = json_decode($file['img_metadata'], true);
			}

			$author = $metaData['Artist'] ?? '';
			$copyrightHolder = $metaData['Copyright'] ?? '';
			if ( is_array($copyrightHolder) ) {
				$copyrightHolder = '';
			}
			$copyrightHolderContainsYear = preg_match("/\d{4}/", $copyrightHolder);

			if (
				( $author || $copyrightHolder ) &&
				$author !== 'Picasa' &&
				! $copyrightHolderContainsYear
			) {
				$result .= "\n# [[:File:$fileName]]";
			}
		}
		return $result;
	}

	private function logInToWikipedia() {
		$this->wikiAPI = new wikipedia();
		$this->wikiAPI->beQuiet();
		$this->wikiAPI->http->useragent = '[[en:User:NovemBot]] task B, owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
		$this->wikiAPI->login($this->wiki_username, $this->wiki_password);
	}

	private function makeEdit($wikicode, $pageTitle) {
		$this->wikiAPI->edit(
			$pageTitle,
			$wikicode,
			'NovemBot Task B'
		);
	}

}

$workingDirectory = posix_getpwuid(posix_getuid());
$databaseConfigFile = parse_ini_file($workingDirectory['dir'] . "/replica.my.cnf");
$sff = new SuspiciousImageFinder();
$sff->execute($_GET, $http_get_password, $argv, $wiki_username, $wiki_password, $databaseConfigFile);