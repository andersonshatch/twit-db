<?php 
chdir(dirname(__FILE__));
if(is_readable('config.php')) {
	require_once 'config.php';
} else {
	header('Location: setup.php');
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="bootstrap/docs/assets/css/bootstrap.css" />
<link rel="stylesheet" href="bootstrap/docs/assets/css/bootstrap-responsive.css" />
<link rel="stylesheet" href="css/twitter-db.css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Twit-DB search</title>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap-tooltip.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap-typeahead.js"></script>
<script type="text/javascript" src="js/main.js"></script>
<script type="text/javascript" src="js/timeago.js"></script>
<script type="text/javascript" src="js/mustache.min.js"></script>
<script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
</head>
<body>

<div class="navbar navbar-fixed-top navbar-inverse">
	<div class="navbar-inner">
		<div class="container">
			<a id="logo" class="brand" href="#">Twit-DB</a>
			<form id="search-form" class="form-search navbar-search" method="POST">
				<input id="search-text" class="search-query" name="text" value="" placeholder="Text" />
				( <input id="search-username" class="search-query"  name="username" value="" placeholder="Username" autocomplete="off" />
				Retweets <input id="search-retweets" class="" name="retweets" type="checkbox" checked /> )
				<input class="search-query" type="submit" value="Submit" />
			</form>
		</div><!-- container -->
	</div><!-- navbar-inner -->
</div><!-- navbar -->
<div id="container" class="container">
<div class="content">
<div class="page-header">
	<span id="tweet-count"></span>
</div>
<div id="stream">
</div>
<a href="#" id="loadMore" class="btn large span12">Load More</a>
</div><!--content-->
</div><!--container-->
<script type="text/mustache-template" id="tweet-template">
{{#tweets}}
<div class="row tweet" data-item-id="{{id}}"{{#relevance}}data-relevance-value="{{relevance}}"{{/relevance}}>
	<div class="row span12 columns">
		<div class="span1 columns tweet-image">
			<br />
			<a class="user-profile-link" href="https://twitter.com/{{user.screenName}}">
				<img width="48" height="48" class="user-profile-link" alt="{{user.screenName}}'s avatar" src="{{user.profileImageUrl}}">
			</a>
		</div>
		<div class="span10 columns tweet-content">
			<div class="tweet-names">
				<h4>
					<a class="tweet-full-name user-profile-link" href="https://twitter.com/{{user.screenName}}" data-screen-name="{{user.screenName}}">{{user.name}}</a>
					<small class="tweet-screen-name">
						<a class="tweet-screen-name user-profile-link" href="https://twitter.com/{{user.screenName}}">@{{user.screenName}}</a>
						{{#retweetedByUserId}}
							<span class="retweet-icon"></span>by <a class="tweet-screen-name user-profile-link" href="https://twitter.com/{{retweetedByScreenName}}">@{{retweetedByScreenName}}</a>
						{{/retweetedByUserId}}
					</small>
				</h4>
			</div>
			<div class="tweet-text">
				{{{text}}}
			</div>

			<blockquote class="tweet-metadata">
				<small>
					<a href="https://twitter.com/{{user.screenName}}/status/{{id}}" class="tweet-timestamp" target="_blank" title="{{timestampTitle}}" data-timestamp="{{dateTime}}">
						{{createdAt}}
					</a>
					<span class="tweet-source">
						via {{{source}}}{{#placeFullName}} from {{placeFullName}}{{/placeFullName}}
					</span>
					<span class="tweet-actions hidden">
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
