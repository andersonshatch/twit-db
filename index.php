<?php $time_start = microtime(true);
ob_implicit_flush(1);
if(is_readable('config.php')){
	require_once 'config.php';
}else{
	header('Location: setup.php');
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="bootstrap/bootstrap.min.css" />
<link rel="stylesheet" href="css/twitter-db.css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Twit-DB search</title>
<script type="text/javascript" src="//platform.twitter.com/anywhere.js?id=<?php echo TWITTER_CONSUMER_KEY; ?>&amp;v=1"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js"></script>
<script type="text/javascript" src="js/lazyload.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap-twipsy.js"></script>
<script type="text/javascript" src="js/main.js"></script>
<script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
</head>
<body>

<div class="topbar">
	<div class="fill">
		<div class="container">
			<h3><a href="#" style="margin-left: -22px;">Twit-DB</a></h3>
			<form id="search-form" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="POST">
				<input id="search-text" class="" name="text" value="<?php if(array_key_exists("text", $_POST)) echo htmlentities($_POST['text']); ?>" placeholder="Text" />
				( <input id="search-username" class=""  name="username" value="<?php if(array_key_exists("username", $_POST)) echo htmlentities($_POST['username']); ?>" placeholder="Username <?php if( defined("MENTIONS_TIMELINE") && MENTIONS_TIMELINE == "true") echo "(@me for mentions)";?>" />
				Retweets <input id="search-retweets" class="" name="retweets" type="checkbox" <?php if(array_key_exists("retweets", $_POST) && $_POST['retweets'] = 'on') echo 'checked="checked"'; ?> /> )
				<input id="search-limit" class="" name="limit" value="<?php if(array_key_exists("limit", $_POST)) echo htmlentities($_POST['limit']); ?>" placeholder="Limit" />
				<input type="submit" value="Submit" />
			</form>
		</div><!-- container -->
	</div><!-- fill -->
</div><!-- topbar -->

<?php

include 'lib/Timesince.php';
require_once 'lib/linkify_tweet.php';
require_once 'lib/buildQuery.php';

$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
$mysqli->set_charset("utf8");
$GLOBALS['queryCount'] = 0;
$queryString = buildQuery($_POST, $mysqli);
$query = $mysqli->query($queryString);
$GLOBALS['queryCount']++;
?>
<div id="container" class="container">
<div class="content">
<div class="stream" style="padding-top: 60px;">
<?php
$rowCount = $mysqli->affected_rows;
echo <<<HTML
	<div class="page-header">
		$rowCount matching tweets<br />
	</div>
HTML;

$favoriteURLBase = "https://twitter.com/intent/favorite/?tweet_id=";
$retweetURLBase = "https://twitter.com/intent/retweet/?tweet_id=";
$replyURLBase = "https://twitter.com/intent/tweet/?in_reply_to=";
while ($row = $query->fetch_array(MYSQLI_ASSOC)){

    $favouriteURL = "$favoriteURLBase{$row['id']}";
    $retweetURL = "$retweetURLBase{$row['id']}";
    $replyURL = "$replyURLBase{$row['id']}";
    
    $text = linkify_tweet($row['text'], $row['entities_json']);
    if($row['retweeted_by_user_id'] != null){
	$tweetMeta = <<<HTML
	    \n<span class="retweet-icon"></span>by <a class="tweet-screen-name user-profile-link" href="https://twitter.com/{$row['retweeted_by_screen_name']}">{$row['retweeted_by_screen_name']}</a>
HTML;
    }else
	$tweetMeta = "";

    if($row['place_full_name'] != null){
	$extraIcons = <<<HTML
	    	\n<div class="extra-icons">
	    		<span class="geo-pin"></span>
		</div>
HTML;
	$placeName = <<<HTML
		from {$row['place_full_name']}
HTML;
    }else{
	$extraIcons = "";
	$placeName = "";
    }
$createdat = date_create_from_format('Y-m-d H:i:s', $row['created_at']);
$timestamp = Timesince($createdat->format('U'));
echo <<<HTML
	<div class="row tweet" data-item-id="{$row['id']}">
		<div class="span16 columns">
			<div class="span1 columns tweet-image">
				<br />
				<a class="user-profile-link" href="https://twitter.com/{$row['screen_name']}">
					<img width="48" height="48" class="user-profile-link" alt="{$row['screen_name']}'s avatar" original={$row['profile_image_url']}>
				</a>
			</div>
			<div class="span14 columns tweet-content">
				<div class="tweet-names">
					<h3><a class="tweet-screen-name user-profile-link" href="https://twitter.com/{$row['screen_name']}">{$row['screen_name']}</a>
					<small class="tweet-full-name">{$row['name']}$tweetMeta</small>
					</h3>
				</div>
				<div class="tweet-text">
					$text
				</div>
			
				<blockquote class="tweet-metadata">
					<small>
						<a href="https://twitter.com/#!/{$row['screen_name']}/status/{$row['id']}" class="tweet-timestamp" target="_blank" title="{$createdat->format('G:i M jS \'y')}">
							$timestamp ago 
						</a>
						<span class="tweet-source">
							via {$row['source']} $placeName 
						</span>
						<span class="tweet-actions" style="visibility: hidden;">
							<a href="{$favouriteURL}" class="favorite-action" title="Favorite">
								<i></i>Favorite
							</a>
						<a href="$retweetURL" class="retweet-action" title="Retweet">
							<i></i>Retweet
						</a>
						<a href="$replyURL" class="reply-action" title="Reply">
							<i></i>Reply
						</a>
						</span>
					</small>
				</blockquote>
			</div>
		</div>
	</div>

HTML;



}

$mysqli->close();
?>
<script type="text/javascript">
twttr.anywhere(function (T) {
	T(".username, .tweet-screen-name, .icons > em > a").hovercards({ linkify: false, expanded: true });
});
</script>
</div><!--content-->
<div class="page-header"></div>
<div class="gen-stats">
<?php $time_end = microtime(true);
$time = $time_end - $time_start;
echo "Created in ".substr($time, 0, 6)." seconds.<br />Used {$GLOBALS['queryCount']} ";
$queries = ($GLOBALS['queryCount'] == 1) ? "query." : "queries.";
echo $queries;
?>
</div><!--gen-stats-->
</div><!--content-->
</div><!--container-->
</body>
</html>
