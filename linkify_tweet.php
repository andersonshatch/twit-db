<?php

/*
 * Puts links around usernames, hashtags, URLs and twitter pictures in the provided tweet.
 * If the tweet has a display URL it will be shown whilst the link will go through the t.co address.
 * @param string $text Text to be linkified
 * @param string $urlEntities JSON from tweet's entities
 * @return string Tweet with wrapped usernames, hashtags and urls.
 */
function linkify_tweet($text, $entitiesJSON){
	if($entitiesJSON == null){
		return $text;
	}
	$entities = json_decode($entitiesJSON);
	$replacements = array();
	$keys = array();
	
	$urlRel = "external nofollow";
	$urlTarget = "_blank";
	
	$pictureClass = "twitter-picture ";
	$usernameClass = "username ";
	$hashtagClass = "hashtag ";
	$urlClass = "";
	
	foreach($entities as $type => $things){
		foreach($things as $value){

			$media_url = "";
			$class = $urlClass;
			$href = "";
			switch($type){
				case "media":
					$media_url = array_key_exists('HTTPS', $_SERVER) ?
						"picture-url=\"$value->media_url_https\" "
						: "picture-url=\"$value->media_url\" ";
					$class.=$pictureClass;
					//fall through to the url case as a picture is mostly just a url for this purpose
				case "urls":
					$class.=$urlClass;
					$url = empty($value->expanded_url) ? $value->url : $value->expanded_url;
					$display = isset($value->display_url) ? $value->display_url : str_replace('http://', '', $url);
					$href = "<a class=\"$class\" rel=\"$urlRel\" target=\"$urlTarget\" href=\"$value->url\" title=\"$url\" $media_url>$display</a>";
					break;
				case "hashtags":
					$href = "<a class=\"$hashtagClass\" rel=\"$urlRel\" target=\"$urlTarget\" href=\"http://search.twitter.com/search?q=%23$value->text\">#$value->text</a>";
					break;
				case "user_mentions":
					$href = "@<a class=\"$usernameClass\" rel=\"$urlRel\" target=\"$urlTarget\" href=\"https://twitter.com/$value->screen_name\">$value->screen_name</a>";
					break;

			}
			store_replacement($keys, $replacements, $value->indices, $href, $text);
		}
	}
	$entified_tweet = $text;
	ksort($replacements);
	$replacements = array_reverse($replacements, TRUE);
	foreach($replacements as $key => $value){
		$replacement = mb_substr($entified_tweet, 0, $key, 'UTF-8').$value; //replaced text upto end of replaced item
		$post_replacement = mb_substr($entified_tweet, $key + strlen($keys[$key]), strlen($entified_tweet), 'UTF-8'); //text after replaced item
		$entified_tweet = $replacement.$post_replacement; //replacement and rest combined.
	}
	return $entified_tweet;

}

function store_replacement(&$keyStore, &$replacementStore, $indices, $replacement, $text){
	$keyStore[$indices[0]] = mb_substr(
		$text, $indices[0], $indices[1] - $indices[0], 'UTF-8');
	$replacementStore[$indices[0]] = $replacement;
}

?>
