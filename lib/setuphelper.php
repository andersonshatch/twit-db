<?php

if(!array_key_exists('action', $_GET)) {
    header('HTTP/1.1 400 Bad Request');
    exit();
}

session_start();

switch($_GET['action']) {
	case "checkdb":
		@$mysqli = new mysqli($_POST['db_host'], $_POST['db_uname'], $_POST['db_pass']);
		if($mysqli->connect_error) {
			//ERROR//
			header('HTTP/1.1 403 Forbidden');
			echo generateBanner("alert-error", "Connection failed. Check Credentials. ({$mysqli->connect_error})");
			exit;
		}
		if(!$mysqli->select_db($_POST['db_name'])) {
			//ERROR//
			header('HTTP/1.1 403 Forbidden');
			echo generateBanner("alert-warning", "Failed selecting database '<i>{$_POST['db_name']}</i>'. Connected ok, does database exist?");
			$mysqli->close();
			exit;
		}
		//SUCCESS
		$mysqli->close();
		exit;
		break;
	case "checktwitter":
		define("TWITTER_CONSUMER_KEY", $_POST['consumer-key']);
		define("TWITTER_CONSUMER_SECRET", $_POST['consumer-secret']);
		includeTwitterAsyncFiles();
		try {
			$twitterObj = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
			$authURL = $twitterObj->getAuthorizeUrl(null, array("oauth_callback" => $_POST['oauth_callback']));
			$_SESSION['consumer_key'] = TWITTER_CONSUMER_KEY;
			$_SESSION['consumer_secret'] = TWITTER_CONSUMER_SECRET;
		} catch(EpiOAuthUnauthorizedException $e) {
			header('HTTP/1.1 403 Forbidden');
			echo generateBanner("alert-error", "Couldn't get authorize link, check app credentials and retry");
			exit;
		}
		echo "<a data-url=\"$authURL\" target=\"_blank\" id=\"signin-button\"><img src=\"css/sign-in-with-twitter-d.png\" /></a>";
		exit;
		break;
	case "signin":
		includeTwitterAsyncFiles();
		$twitterObj = new EpiTwitter($_SESSION['consumer_key'], $_SESSION['consumer_secret']);
		$twitterObj->setToken($_GET['oauth_token']);
		$token = $twitterObj->getAccessToken(array('oauth_verifier' => $_GET['oauth_verifier']));
		//TODO: HANDLE EpiOAuthUnauthorizedException with setToken.
		$twitterObj->setToken($token->oauth_token, $token->oauth_token_secret);
		$failCount = 0;
		while(true) {
			try {
				$user = $twitterObj->get('/account/verify_credentials.json');
				break;
			} catch(EpiTwitterException $e) {
				if($failCount++ > 2) {
					header('HTTP/1.1 403 Forbidden');
					echo generateBanner("alert-error", $e->getMessage());
					exit;
				}
				continue;
			}
		}
		echo "Authenticated successfully as {$user->screen_name}";
		echo "<script type=\"text/javascript\">window.opener.setUserCredentials(\"{$token->oauth_token}\", \"{$token->oauth_token_secret}\", \"{$user->screen_name}\"); window.close();</script>";
		exit;
		break;
	case "submit-all":
		createConfigFile();
		break;
	default:
		header('HTTP/1.1 403 Forbidden');
		exit;
	
}

function includeTwitterAsyncFiles() {
	$dependencies = array('../twitter-async/EpiCurl.php', '../twitter-async/EpiOAuth.php', '../twitter-async/EpiTwitter.php');
	foreach($dependencies as $file) {
		if(!(include $file)) {
			header ('HTTP/1.1 500 Internal Server Error.');
			echo dependencyBanner($file);
			exit;
		}
	}
}

function generateBanner($class, $message) {
	return "<div class=\"alert $class\"><p>$message</p></div>";
}

function dependencyBanner($fileName) {
	return generateBanner("error", "Couldn't find $fileName. Run <code>git submodule --init</code> in this directory to fix.");
}

function createConfigFile() {

	foreach($_POST as $key => $val) {
		$_POST[$key] = addslashes($val);
	}
	require_once 'additional_users.php';
    $otherUsers = create_users_string(create_users_array($_POST['other-users']));
	$mentions = "";
	if(array_key_exists('mentions-timeline', $_POST) && $_POST['mentions-timeline'] == 'on')
		$mentions = "define('MENTIONS_TIMELINE', 'true');";
	$timezone = "";
	if(array_key_exists('timezone', $_POST) && $_POST['timezone'] != '')
		$timezone = "\n\tdate_default_timezone_set('{$_POST['timezone']}');\n";

	$output = <<<MARK
<?php
	$timezone
	define('DB_HOST', '{$_POST['db_host']}');
	define('DB_USERNAME', '{$_POST['db_uname']}');
	define('DB_PASSWORD', '{$_POST['db_pass']}');
	define('DB_NAME', '{$_POST['db_name']}');

	define('TWITTER_CONSUMER_KEY', '{$_POST['consumer-key']}');
	define('TWITTER_CONSUMER_SECRET', '{$_POST['consumer-secret']}');
	define('TWITTER_USER_TOKEN', '{$_POST['user-token']}');
	define('TWITTER_USER_SECRET', '{$_POST['user-secret']}');
	
	define('ADDITIONAL_USERS', '$otherUsers');
	$mentions

?>

MARK;

	if(!is_writable('config.php') && !(!file_exists('config.php') && is_writeable('.'))) {
		header('HTTP/1.1 403 Forbidden');
		echo generateBanner("alert-warning", "Can't write to config.php, copy the contents of the text box below and save it as config.php");
		echo "<textarea id=\"config-output\" class=\"span12\" rows=\"17\" wrap=\"off\">$output</textarea>";
		exit;
	}
	$handle = fopen('config.php', 'w');
	fwrite($handle, $output);
	fclose($handle);
	echo generateBanner("alert-success", "Config file written successfully. Schedule gettweets.php to run automatically, and use index.php to search.");

}

?>
