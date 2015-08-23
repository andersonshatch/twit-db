<?php

require_once('../lib/ConfigHelper.php');
require_once('../lib/HashtagUtil.php');
ConfigHelper::requireConfig("../config.php");

header('Content-Type: application/json; charset=utf-8');

if(!array_key_exists('q', $_GET)) {
	header('HTTP/1.1 400 Bad Request');
	echo json_encode([]);
	exit;
}

$mysqli = ConfigHelper::getDatabaseConnection();

$results = HashtagUtil::search($_GET['q'], $mysqli);

echo json_encode($results);

$mysqli->close();

?>