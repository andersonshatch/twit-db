<?php

chdir(dirname(__FILE__));
require_once 'ConfigHelper.php';
require_once 'IDQueryBuilder.php';
require_once 'QueryHolder.php';
require_once 'QueryUtils.php';

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
		do {
			$this->idsToLookup[] = $this->id;
			$this->fetchInReplyTo(array($this->id));
			$this->id = $this->getInReplyToStatusId();
		} while(!is_null($this->id));

		if (!empty($this->idsToLookup)) {
			list($queryString, $queryParams) = IDQueryBuilder::buildQuery($this->idsToLookup);
			$this->queries[] = $queryString;

			$query = QueryHolder::prepareAndHoldQuery($this->mysqli, $queryString);
			QueryUtils::bindQueryWithParams($query, $queryParams);

			$query->execute();
			$results = $query->get_result();

			return $results->fetch_all(MYSQLI_ASSOC);
		} else {
			return array();
		}
	}

	public function getQueries() {
		return $this->queries;
	}

	private function getInReplyToStatusId() {
		$inReplyToQueryString = "SELECT in_reply_to_status_id FROM tweet WHERE id = ?";
		$query = QueryHolder::prepareAndHoldQuery($this->mysqli, $inReplyToQueryString);
		$this->queries[] = $inReplyToQueryString;
		QueryUtils::bindQueryWithParams($query, [$this->id]);
		$query->execute();
		$result = $query->get_result();
		$result = $result ? $result->fetch_row() : null;

		return $result ? $result[0] : null;
	}

	private function fetchInReplyTo($ids, $recurse = true) {
		if (empty($ids)) {
			return;
		}

		$repliesQueryString = "SELECT id FROM tweet WHERE in_reply_to_status_id IN ".QueryUtils::parameterPlaceholderString(count($ids));

		$this->queries[] = $repliesQueryString;
		$query = QueryHolder::prepareAndHoldQuery($this->mysqli, $repliesQueryString);
		QueryUtils::bindQueryWithParams($query, $ids);
		$query->execute();
		$result = $query->get_result()->fetch_all();

		if ($result) {
			$results = [];
			foreach ($result as $row) {
				if (!in_array($row[0], $this->idsToLookup)) {
					$this->idsToLookup[] = $row[0];
					if ($recurse) {
						$results[] = $row[0];
					}
				}
			}
			$this->fetchInReplyTo($results);
		}
	}
}

?>
