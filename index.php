<?php 
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
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap-twipsy.js"></script>
<script type="text/javascript" src="js/main.js"></script>
<script type="text/javascript" src="js/timeago.js"></script>
<script type="text/javascript" src="js/mustache.js"></script>
<script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
</head>
<body>

<div class="navbar navbar-fixed">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand" href="#" style="margin-left: -22px;">Twit-DB</a></h3>
			<form id="search-form" class="form-search navbar-search" method="POST">
				<input id="search-text" class="search-query" name="text" value="" placeholder="Text" />
				( <input id="search-username" class="search-query"  name="username" value="" placeholder="Username <?php if( defined("MENTIONS_TIMELINE") && MENTIONS_TIMELINE == "true") echo "(@me for mentions)";?>" />
				Retweets <input id="search-retweets" class="" name="retweets" type="checkbox" /> )
				<input class="search-query" type="submit" value="Submit" />
			</form>
		</div><!-- container -->
	</div><!-- navbar-inner -->
</div><!-- navbar -->
<div id="container" class="container">
<div class="content" style="padding-top: 60px;">
<div class="page-header">
	<span id="tweet-count"></span>
</div>
<div id="stream">
</div>
<a href="#" id="loadMore" class="btn large span16" style="display: none">Load More</a>
<script type="text/javascript">
twttr.anywhere(function (T) {
	T(".username, .tweet-screen-name, .icons > em > a").hovercards({ linkify: false, expanded: true });
});
</script>
</div><!--content-->
</div><!--container-->
<script type="text/mustache-template" id="tweet-template">
{{#tweets}}
<div class="row tweet" data-item-id="{{id}}">
	<div class="row span16 columns">
		<div class="span1 columns tweet-image">
			<br />
			<a class="user-profile-link" href="https://twitter.com/{{screen_name}}">
				<img width="48" height="48" class="user-profile-link" alt="{{screen_name}}'s avatar" src="{{profile_image_url}}">
			</a>
		</div>
		<div class="span14 columns tweet-content">
			<div class="tweet-names">
				<h3>
					<a class="tweet-screen-name user-profile-link" href="https://twitter.com/{{screen_name}}">{{screen_name}}</a>
					<small class="tweet-full-name">
						{{name}}
						{{#retweeted_by_user_id}}
							<span class="retweet-icon"></span>by <a class="tweet-screen-name user-profile-link" href="https://twitter.com/{{retweeted_by_screen_name}}">{{retweeted_by_screen_name}}</a>
						{{/retweeted_by_user_id}}
					</small>
				</h3>
			</div>
			<div class="tweet-text">
				{{{text}}}
			</div>

			<blockquote class="tweet-metadata">
				<small>
					<a href="https://twitter.com/{{screen_name}}/status/{{id}}" class="tweet-timestamp" target="_blank" title="{{timestamp_title}}" data-timestamp="{{datetime}}">
						{{created_at}}
					</a>
					<span class="tweet-source">
						via {{{source}}}{{#place_full_name}} from {{place_full_name}}{{/place_full_name}}
					</span>
					<span class="tweet-actions" style="visibility: hidden;">
						<a href="https://twitter.com/intent/favorite/?tweet_id={{id}}" class="favorite-action" title="Favorite">
							<i></i>Favorite
						</a>
						<a href="https://twitter.com/intent/retweet/?tweet_id={{id}}" class="retweet-action" title="Retweet">
							<i></i>Retweet
						</a>
						<a href="https://twitter.com/intent/tweet/?in_reply_to={{id}}" class="reply-action" title="Reply">
							<i></i>Reply
						</a>
					</span>
				</small>
			</blockquote>
		</div>
	</div>
</div>
{{/tweets}}
</script>
</body>
</html>
