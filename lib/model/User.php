<?php

namespace TwitDB\Model;

class User implements \JsonSerializable {
	private $id;
	private $name;
	private $profileImageUrl;
	private $screenName;

	public function __construct(array $array) {
		$this->id = $array['user_id'];
		$this->name = $array['name'];
		$this->profileImageUrl = str_replace(['http://', '_normal'], ['https://', '_bigger'], $array['profile_image_url']);
		$this->screenName = $array['screen_name'];
	}

	public function jsonSerialize(): array {
		return [
			'id'                => $this->id,
			'name'              => $this->name,
			'profileImageUrl'   => $this->profileImageUrl,
			'screenName'        => $this->screenName
		];
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getProfileImageUrl() {
		return $this->profileImageUrl;
	}

	public function setProfileImageUrl($profileImageUrl) {
		$this->profileImageUrl = str_replace(['http://', '_normal'], ['https://', '_bigger'], $profileImageUrl);
	}

	public function getScreenName() {
		return $this->screenName;
	}

	public function setScreenName($screenName) {
		$this->screenName = $screenName;
	}

}
