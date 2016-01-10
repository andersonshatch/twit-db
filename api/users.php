<?php

require_once("../lib/ConfigHelper.php");
require_once("../lib/UserUtil.php");
ConfigHelper::requireConfig("../config.php");

header('Content-Type: application/json; charset=utf-8');

if(!array_key_exists('q', $_GET)) {
	header('HTTP/1.1 400 Bad Request');
	echo json_encode(array());
	exit;
}

$mysqli = ConfigHelper::getDatabaseConnection();

$results = UserUtil::search($_GET['q'], $mysqli);

echo json_encode($results);

?>
