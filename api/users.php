<?php

require_once("../lib/ConfigHelper.php");
ConfigHelper::requireConfig("../config.php");

header('Content-Type: application/json; charset=utf-8');

if(!array_key_exists('q', $_GET)) {
	header('HTTP/1.1 400 Bad Request');
	echo json_encode(array());
	exit;
}

$mysqli = ConfigHelper::getDatabaseConnection();

$sql = "SELECT screen_name AS screenName, name, profile_image_url AS imageUrl FROM user WHERE screen_name LIKE '".$mysqli->real_escape_string($_GET['q'])."%' OR name LIKE '%".$mysqli->real_escape_string($_GET['q'])."%' LIMIT 8";
$query = $mysqli->query($sql);

echo json_encode($query->fetch_all(MYSQLI_ASSOC));

$mysqli->close();

?>
