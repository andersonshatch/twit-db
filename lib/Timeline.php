<?php

require_once dirname(__file__).'/TimelineType.php';
require_once dirname(__file__).'/QueryHolder.php';

class Timeline {
	//persisted fields
	private $id;
	private $enabled = true;
	private $lastSeenId;
	private $lastUpdatedAt;
	private $name;

	//transient/computed
	private $sinceId;
	private $maxId;
	private $timelineType;

	//dependencies
	private $mysqli;

	const SQL_DATE_TIME_FORMAT = 'Y-m-d G:i:s';

	public function __construct(mysqli $mysqli) {
		$this->mysqli = $mysqli;
	}

	public function save() {
		$lastUpdatedSQLValue = $this->lastUpdatedAt == null ? null : $this->lastUpdatedAt->format(self::SQL_DATE_TIME_FORMAT);

		//don't persist the lastSeenId when this is a favorite timeline -- have to lookup all favorites to ensure nothing was missed
		//...on startup, favorite table is truncated so we start from the earliest available favorite next time around
		$lastSeenId = $this->getTimelineType() == TimelineType::FavoriteTimeline ? null : $this->lastSeenId;

		$enabled = $this->enabled ? 1 : 0;
		if(!$this->id) {
			$insertSQL = "INSERT INTO timeline(name, last_seen_id, enabled, last_updated_at) VALUES(?, ?, ?, ?)";
			$statement = QueryHolder::prepareAndHoldQuery($this->mysqli, $insertSQL);
			$statement->bind_param('ssss', $this->name, $lastSeenId, $enabled, $lastUpdatedSQLValue);
			$statement->execute();
			$this->id = $this->mysqli->insert_id;
		} else {
			$updateSQL = "UPDATE timeline SET name = ?, last_seen_id = ?, enabled = ?, last_updated_at = ? WHERE timeline_id = ?";
			$statement = QueryHolder::prepareAndHoldQuery($this->mysqli, $updateSQL);
			$statement->bind_param('sssss', $this->name, $lastSeenId, $enabled, $lastUpdatedSQLValue, $this->id);
			$statement->execute();
		}
	}

	public static function all(mysqli $mysqli, $includeDisabled = false) {
		$queryString = "SELECT timeline_id, name, last_seen_id, enabled, last_updated_at FROM timeline";
		if(!$includeDisabled) {
			$queryString .= " WHERE enabled = 1";
		}

		$timelines = [];

		foreach($mysqli->query($queryString)->fetch_all(MYSQLI_ASSOC) as $row) {
			$timeline = new Timeline($mysqli);
			$timeline->initWithArray($row);
			$timelines[] = $timeline;
		}

		return $timelines;
	}

	private function initWithArray(array $array) {
		$this->id = $array['timeline_id'];
		$this->enabled = $array['enabled'];
		$this->lastUpdatedAt = $array['last_updated_at'] == null ? null : DateTime::createFromFormat(self::SQL_DATE_TIME_FORMAT, $array['last_updated_at']);
		$this->lastSeenId = $array['last_seen_id'];
		$this->name = $array['name'];
		$this->sinceId = $this->lastSeenId;
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function isEnabled() {
		return $this->enabled;
	}

	public function setEnabled($enabled) {
		$this->enabled = $enabled;
	}

	public function getLastSeenId() {
		return $this->lastSeenId;
	}

	public function setLastSeenId($lastSeenId) {
		$this->lastSeenId = $lastSeenId;
	}

	public function getLastUpdatedAt() {
		return $this->lastUpdatedAt;
	}

	public function setLastUpdatedAt(DateTime $lastUpdatedAt) {
		$this->lastUpdatedAt = $lastUpdatedAt;
	}

	public function getMaxId() {
		return $this->maxId;
	}

	public function setMaxId($maxId) {
		$this->maxId = $maxId;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getSinceId() {
		return $this->sinceId;
	}

	public function setSinceId($sinceId) {
		$this->sinceId = $sinceId;
	}

	public function getRequestEndpoint() {
		switch($this->getTimelineType()) {
			case TimelineType::HomeTimeline:
				return "/statuses/home_timeline.json";
				break;
			case TimelineType::MentionsTimeline:
				return "/statuses/mentions_timeline.json";
				break;
			case TimelineType::UserTimeline:
				return "/statuses/user_timeline.json";
				break;
			case TimelineType::ListTimeline:
				return "/lists/statuses.json";
				break;
			case TimelineType::FavoriteTimeline:
				return "/favorites/list.json";
				break;
			case TimelineType::SearchTimeline:
				return "/search/tweets.json";
				break;
		}
	}

	public function getRequestParameters() {
		$baseParams = ["count" => 180, "include_rts" => "true", "include_entities" => "true", "tweet_mode" => "extended"];
		if($this->sinceId) {
			$baseParams["since_id"] = $this->sinceId;
		}
		if($this->maxId) {
			$baseParams["max_id"] = $this->maxId;
		}
		switch($this->getTimelineType()) {
			case TimelineType::HomeTimeline:
			case TimelineType::MentionsTimeline:
			case TimelineType::FavoriteTimeline:
				return $baseParams;
			case TimelineType::UserTimeline:
				return array_merge($baseParams, ["screen_name" => substr($this->name, 1)]);
				break;
			case TimelineType::ListTimeline:
				$listIdentifierComponents = explode("/", substr($this->name, 1));
				return array_merge($baseParams, ["owner_screen_name" => $listIdentifierComponents[0], "slug" => $listIdentifierComponents[1]]);
				break;
			case TimelineType::SearchTimeline:
				return array_merge($baseParams, ["q" => substr($this->name, strlen("search: "))." -filter:retweets", "result_type" => "recent"]);
				break;
		}
	}

	public function getTimelineType() {
		if($this->timelineType !== null) {
			return $this->timelineType;
		}
		switch($this->name) {
			case "home":
				$this->timelineType = TimelineType::HomeTimeline;
				break;
			case "mentions":
				$this->timelineType = TimelineType::MentionsTimeline;
				break;
			case "favorites":
				$this->timelineType = TimelineType::FavoriteTimeline;
				break;
			case $this->name[0] == "@" && strpos($this->name, "/") === false:
				$this->timelineType = TimelineType::UserTimeline;
				break;
			case strpos($this->name, "search: ") !== false:
				$this->timelineType = TimelineType::SearchTimeline;
				break;
			default:
				$this->timelineType = TimelineType::ListTimeline;
				break;
		}

		return $this->timelineType;
	}

}
