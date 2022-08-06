<?php

include("botclasses.php");
include("logininfo.php");

class SuspiciousFileFinder {
	private function setHeader() {
		header('Content-Type:text/plain; charset=utf-8; Content-Encoding: none');
	}

	private function setErrorReporting() {
		ini_set("display_errors", 1);
		error_reporting(E_ALL);
	}

	private function checkPermissions() {
		global $_GET, $http_get_password, $argv;

		if ( ($_GET['password'] ?? '') != $http_get_password && ($argv[1] ?? '') != $http_get_password ) {
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
		$ts_pw = posix_getpwuid(posix_getuid());
		$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
		// Must use database_p. database will not work.
		$this->enwiki = new PDO("mysql:host=enwiki.analytics.db.svc.wikimedia.cloud;dbname=enwiki_p", $ts_mycnf['user'], $ts_mycnf['password']);
		$this->enwiki->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	}

	private function getFiles() {
		$this->echoAndFlush("\n\nGenerating list of files to check...");
		$query = $this->enwiki->prepare('
			SELECT img_name, img_metadata
			FROM categorylinks
			JOIN page ON page_id = cl_from
			JOIN image ON img_name = page_title
			WHERE cl_to = "Self-published_work"
				AND cl_from IN (
					SELECT cl_from
					FROM categorylinks
					WHERE cl_to = "All_free_media"
				)
			ORDER BY img_name ASC
			LIMIT 50000    # due to PHP running out of memory
		');
		$query->execute();
		return $query->fetchAll();
	}

	private function checkFiles($filesToCheck) {
		$this->echoAndFlush("\n\nChecking files. This may take awhile. Note that this is not all of the files due to memory limitations, this is just the first 1500 or so...");
		$result = '';
		foreach ( $filesToCheck as $key => $file ) {
			$fileName = $file['img_name'];

			// Strangely, this data can be either PHP serialized, or JSON. Try both.
			$metaData = @unserialize($file['img_metadata']);
			if ( ! $metaData ) {
				$metaData = json_decode($file['img_metadata'], true);
			}

			$author = $metaData['Artist'] ?? '';
			$copyrightHolder = $metaData['Copyright'] ?? '';
			if ( $author || $copyrightHolder ) {
				$result .= "\n# [[:File:$fileName]]";
			}
		}
		return $result;
	}

	private function logInToWikipedia() {
		global $wiki_username, $wiki_password;
		$this->wikiAPI = new wikipedia();
		$this->wikiAPI->beQuiet();
		$this->wikiAPI->http->useragent = '[[en:User:NovemBot]] task B, owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
		$this->wikiAPI->login($wiki_username, $wiki_password);
	}

	private function makeEdit($wikicode, $pageTitle) {
		$this->wikiAPI->edit(
			$pageTitle,
			$wikicode,
			'NovemBot Task B'
		);
	}
	
	public function execute() {
		$this->setHeader();
		$this->setErrorReporting();
		$this->checkPermissions();
		$this->printPHPVersion();
		$this->connectToSQLDatabases();
		$filesToCheck = $this->getFiles();
		$filesMatchingCriteria = $this->checkFiles($filesToCheck);
		$this->logInToWikipedia();
		$this->makeEdit($filesMatchingCriteria, 'User:Minorax/files');
		$this->echoAndFlush("\n\nAll done!");
	}
}

$sff = new SuspiciousFileFinder();
$sff->execute();