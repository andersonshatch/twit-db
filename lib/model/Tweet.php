<?php

namespace TwitDB\Model;

require_once dirname(__FILE__).'/User.php';

class Tweet implements \JsonSerializable {
	private $id;
	private $createdAt;
	private $dateTime;
	private $entities;
	private $source;
	private $text;
	private $relevance;
	private $retweetedByScreenName;
	private $retweetedByUserId;
	private $placeFullName;
	private $timestampTitle;
	private $user;

	public function __construct(array $array) {
		$this->id = $array['id'];
		$this->createdAt = $array['created_at'];
		$this->source = $array['source'];
		$this->text = $array['text'];
		$this->relevance = array_key_exists('relevance', $array) ? $array['relevance'] : null;
		$this->retweetedByScreenName = array_key_exists('retweeted_by_screen_name', $array) ? $array['retweeted_by_screen_name'] : null;
		$this->retweetedByUserId = array_key_exists('retweeted_by_user_id', $array) ? $array['retweeted_by_user_id'] : null;
		$this->placeFullName = array_key_exists('place_full_name', $array) ? $array['place_full_name'] : null;
		$this->user = new User($array);
	}

	function jsonSerialize() {
		return [
			'id'                    => $this->id,
			'createdAt'             => $this->createdAt,
			'dateTime'              => $this->dateTime,
			'entities'              => $this->entities,
			'source'                => $this->source,
			'text'                  => $this->text,
			'relevance'             => $this->relevance,
			'retweetedByScreenName' => $this->retweetedByScreenName,
			'retweetedByUserId'     => $this->retweetedByUserId,
			'placeFullName'         => $this->placeFullName,
			'timestampTitle'        => $this->timestampTitle,
			'user'                  => $this->user
		];
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
	}

	public function getDateTime() {
		return $this->dateTime;
	}

	public function setDateTime($dateTime) {
		$this->dateTime = $dateTime;
	}

	public function getEntities() {
		return $this->entities;
	}

	public function setEntities(\stdClass $entities) {
		$this->entities = $entities;
	}

	public function getSource() {
		return $this->source;
	}

	public function setSource($source) {
		$this->source = $source;
	}

	public function getText() {
		return $this->text;
	}

	public function setText($text) {
		$this->text = $text;
	}

	public function getRelevance() {
		return $this->relevance;
	}

	public function setRelevance($relevance) {
		$this->relevance = $relevance;
	}

	public function getRetweetedByScreenName() {
		return $this->retweetedByScreenName;
	}

	public function setRetweetedByScreenName($retweetedByScreenName) {
		$this->retweetedByScreenName = $retweetedByScreenName;
	}

	public function getRetweetedByUserId() {
		return $this->retweetedByUserId;
	}

	public function setRetweetedByUserId($retweetedByUserId) {
		$this->retweetedByUserId = $retweetedByUserId;
	}

	public function getPlaceFullName() {
		return $this->placeFullName;
	}

	public function setPlaceFullName($placeFullName) {
		$this->placeFullName = $placeFullName;
	}

	public function getTimestampTitle() {
		return $this->timestampTitle;
	}

	public function setTimestampTitle($timestampTitle) {
		$this->timestampTitle = $timestampTitle;
	}

	public function getUser() {
		return $this->user;
	}

	public function setUser(User $user) {
		$this->user = $user;
	}
}