<?php

chdir(dirname(__FILE__));
require_once "lib/ConfigHelper.php";
ConfigHelper::requireConfig("config.php");
$mysqli = ConfigHelper::getDatabaseConnection();

require_once('lib/storestatusesandusers.php');
require_once('lib/UserUtil.php');

$zip = openZip($argc, $argv);

$pathJson = decodeFile($zip, 'data/js/tweet_index.js');

$archivePaths = array();
$tweetsToProcess = 0;

foreach($pathJson as $path) {
	$archivePaths[] = $path->file_name;
	$tweetsToProcess += $path->tweet_count;
}

$tweetsAdded = 0;
$tweetsProcessed = 0;
$userIds = array();

foreach($archivePaths as $path) {
	$tweets = decodeFile($zip, $path);

	foreach($tweets as $tweet) {
		if(storeTweet($tweet, $mysqli)) {
			$tweetsAdded++;

			if(property_exists($tweet, "retweeted_status")) {
				$userIds[] = $tweet->retweeted_status->user->id;
			}

			$userIds[] = $tweet->user->id;
		}
		$tweetsProcessed++;
	}

	output("\r\033[K"); //rewrite line
	output("Processing tweets ($tweetsProcessed/$tweetsToProcess)");
}

output("\nAdded $tweetsAdded new tweet".(($tweetsAdded == 1) ? "" : "s")."\n");

if($tweetsAdded > 0)
	$usersUpdated = lookupUsers(array_keys(array_flip($userIds)), $mysqli);

$zip->close();

if (!isCli()) {
	echo json_encode(array("tweetsAdded" => $tweetsAdded, "tweetsProcessed" => $tweetsProcessed, "usersUpdated" => $usersUpdated));
}

function lookupUsers($ids, $mysqli) {
	$twitterObj = ConfigHelper::getTwitterObject();

	$lookupChunkSize = 100;
	$chunks = array_chunk($ids, $lookupChunkSize);
	$usersToUpdate = count($ids);
	$usersUpdated = 0;

	foreach($chunks as $chunk) {
		foreach($twitterObj->post('/users/lookup.json', array("user_id" => implode(",", $chunk))) as $user) {
			UserUtil::upsertUser($user, $mysqli);
		}

		$usersUpdated += count($chunk);

		output("\r\033[K"); //rewrite line
		output("Updating users ($usersUpdated/$usersToUpdate)");
	}

	output("\n");

	return $usersUpdated;
}

function decodeFile($zip, $path) {
	$file = $zip->getFromName($path);

	if(!$file) {
		header('http/1.1 400 Bad Request');
		exit("ERROR: Couldn't find $path in zip.\n");;
	}

	$firstBrace = strpos($file, "{");
	$firstBracket = strpos($file, "[");

	if(!$firstBrace && !$firstBracket) {
		header('HTTP/1.1 400 Bad Request');
		exit("ERROR: Invalid JSON in $path within zip.\n");
	}

	if($firstBrace && $firstBracket) {
		$strpos = $firstBrace < $firstBracket ? $firstBrace : $firstBracket;
	} else if ($firstBrace) {
		$strpos = $firstBrace;
	} else {
		$strpos = $firstBracket;
	}

	if(!$strpos || is_null($decoded = json_decode(substr($file, $strpos)))) {
		header('HTTP/1.1 400 Bad Request');
		exit("ERROR: Invalid JSON in $path within zip.\n");
	}

	return $decoded;
}

function openZip($argc, $argv) {
	$fileName = determineFileName($argc, $argv);
	if(!class_exists('ZipArchive')) {
		header('HTTP/1.1 500 Internal Server Error.');
		exit("ERROR: Zip extension not loaded. Install/include it.\n");
	}
	$zip = new ZipArchive;
	$status = $zip->open($fileName);

	if($status !== true) {
		header('HTTP/1.1 400 Bad Request');
		exit("ERROR: Invalid file. (Errno: $status)\n");
	}

	return $zip;
}

function determineFileName($argc, $argv) {
	if(isCli()) {
		if($argc > 1) {
			$fileName = $argv[1];
			if(!is_readable($fileName)) {
				exit("ERROR: $fileName is not readable\n");
			}

			return $fileName;
		} else {
			exit("ERROR: Need to specify zip file to load.\nUsage: php {$argv[0]} <path to archive.zip>\n");
		}
	} else {
		$fileInputName = "archiveFile";
		if(empty($_FILES) || !array_key_exists($fileInputName, $_FILES) || $_FILES[$fileInputName]["size"] == 0) {
			header('HTTP/1.1 400 Bad Request');
			exit("Invalid file");
		} else {
			return $_FILES[$fileInputName]["tmp_name"];
		}
	}
}

function output($text) {
	if(isCli()) {
		echo $text;
	}
}

function isCli() {
	return PHP_SAPI == 'cli';
}

?>
