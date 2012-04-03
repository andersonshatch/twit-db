<?php

function create_users_array($userList) {
	return array_unique(array_filter(array_map('trim', explode(",", urldecode($userList)))));
}

function create_users_string($userArray) {
	return implode(',', $userArray);
}

?>
