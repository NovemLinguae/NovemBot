<?php

class Query {
	/**
	 * Takes 0 seconds
	 */
	public static function getUsersWithPerm($perm, $db) {
		$query = $db->prepare("
			SELECT user_name
			FROM user
			JOIN user_groups ON ug_user = user_id
			WHERE ug_group = '".$perm."'
			ORDER BY user_name ASC;
		");
		$query->execute();
		return $query->fetchAll();
	}

	public static function getGlobalUsersWithPerm($perm, $db) {
		// globaluser is a special SQL table created by [[mw:Extension:CentralAuth]]
		$query = $db->prepare("
			SELECT gu_name
			FROM globaluser
			JOIN global_user_groups ON gug_user = gu_id
			WHERE gug_group = '".$perm."'
			ORDER BY gu_name ASC;
		");
		$query->execute();
		return $query->fetchAll();
	}

	/**
	 * For 10k edits, takes 11 to 22 seconds. Removing ORDER BY doesn't speed it up. For 500
	 * edits, takes 3 minutes. More thorough than relying on extendedconfirmed perm though.
	 * Doing it this way gets us 14,000 more folks than relying on extendedconfirmed perm.
	 */
	public static function getUsersWithEditCount($minimum_edits, $db) {
		$query = $db->prepare("
			SELECT user_name
			FROM user
			WHERE user_editcount >= ".$minimum_edits."
			ORDER BY user_editcount DESC;
		");
		$query->execute();
		return $query->fetchAll();
	}

	/**
	 * Includes former admins
	 */
	public static function getAllAdminsEverEnwiki($db) {
		$query = $db->prepare("
			SELECT DISTINCT REPLACE(log_title, '_', ' ') AS promoted_to_admin
			FROM logging
			WHERE log_type = 'rights'
				AND log_action = 'rights'
				AND log_params LIKE '%sysop%'
			ORDER BY log_title ASC;
		");
		$query->execute();
		return $query->fetchAll();
	}

	/**
	 * Includes former admins
	 */
	public static function getAllAdminsEverMetawiki($db) {
		$query = $db->prepare("
			SELECT DISTINCT REPLACE(REPLACE(log_title, '_', ' '), '@enwiki', '') AS promoted_to_admin
			FROM logging_logindex
			WHERE log_type = 'rights'
				AND log_action = 'rights'
				AND log_title LIKE '%@enwiki'
				AND log_params LIKE '%sysop%'
			ORDER BY log_title ASC
		");
		$query->execute();
		return $query->fetchAll();
	}
}
