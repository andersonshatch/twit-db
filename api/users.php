<?php

require_once('../config.php');
header('Content-Type: application/json; charset=utf-8');

if(!array_key_exists('q', $_GET)) {
	header('HTTP/1.1 400 Bad Request');
	echo json_encode(array());
	exit;
}

$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
$mysqli->set_charset("utf8");

$sql = "SELECT screen_name AS screenName, name, profile_image_url AS imageUrl FROM users WHERE screen_name LIKE '".$mysqli->real_escape_string($_GET['q'])."%' OR name LIKE '%".$mysqli->real_escape_string($_GET['q'])."%' LIMIT 8";
$query = $mysqli->query($sql);

echo json_encode($query->fetch_all(MYSQLI_ASSOC));

?>
