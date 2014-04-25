<?php

chdir(dirname(__FILE__));
require_once 'ConfigHelper.php';

class Conversation {
	private $id;
	private $idsToLookup = array();
	private $mysqli;
	private $queries = array();

	public function __construct($id) {
		$this->mysqli = ConfigHelper::getDatabaseConnection();
		$this->id = $this->mysqli->real_escape_string($id);
	}

	public function getConversation() {
		$table = 'home'; //TODO: change this based on who the tweet is by?
		do {
			$this->idsToLookup[] = $this->id;
			$this->fetchInReplyTo(array($this->id), $table);
			$this->id = $this->getInReplyToStatusId($table);
		} while(!is_null($this->id));

		if (!empty($this->idsToLookup)) {
			$idsCsv = implode($this->idsToLookup, ", ");
			$queryString = "SELECT id, created_at, source, text, retweeted_by_screen_name, retweeted_by_user_id, place_full_name, user_id, entities_json, screen_name, name, profile_image_url, in_reply_to_status_id FROM `$table` NATURAL JOIN users WHERE id IN ($idsCsv) ORDER BY id";
			$this->queries[] = $queryString;

			$query = $this->mysqli->query($queryString);

			$this->mysqli->close();
			return $query->fetch_all(MYSQLI_ASSOC);
		} else {
			$this->mysqli->close();
			return array();
		}
	}

	public function getQueries() {
		return $this->queries;
	}

	private function getInReplyToStatusId($table) {
		$inReplyToQueryString = "SELECT in_reply_to_status_id FROM `$table` WHERE id = {$this->id}";
		$this->queries[] = $inReplyToQueryString;
		$query = $this->mysqli->query($inReplyToQueryString);
		$result = $query ? $query->fetch_row() : null;

		return $result ? $result[0] : null;
	}

	private function fetchInReplyTo($ids, $table, $recurse = true) {
		if (empty($ids)) {
			return;
		}

		$idsCsv = implode($ids, ", ");
		$qs = "SELECT id FROM `$table` WHERE in_reply_to_status_id IN ($idsCsv)";
		$this->queries[] = $qs;
		$query = $this->mysqli->query($qs);
		$result = $query ? $query->fetch_all() : null;
		$results = array();
		if ($result) {
			foreach ($result as $row) {
				if (!in_array($row[0], $this->idsToLookup)) {
					$this->idsToLookup[] = $row[0];
					if ($recurse) {
						$results[] = $row[0];
					}
				}
			}
			$this->fetchInReplyTo($results, $table);
		}
	}
}

?>
