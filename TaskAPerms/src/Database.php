<?php

class Database {
	static function create($databaseName) {
		// get database credentials
		// TODO: inject these globals
		$ts_pw = posix_getpwuid(posix_getuid());
		$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");

		// Must use database_p. database will not work.
		$pdo = new PDO("mysql:host=$databaseName.analytics.db.svc.wikimedia.cloud;dbname={$databaseName}_p", $ts_mycnf['user'], $ts_mycnf['password']);

		// Turn on error reporting
		$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

		return $pdo;
	}
}
