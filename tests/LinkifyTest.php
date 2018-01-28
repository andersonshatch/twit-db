<?php

chdir(dirname(__FILE__));
require_once '../lib/linkify_tweet.php';

class LinkifyTest extends \PHPUnit\Framework\TestCase {

	public function testTweetWithJustText() {
		$testText = 'blah blah tweet without any usernames or hashtags or links';
		$this->assertEquals($testText, linkify_tweet($testText, null), "Text only tweet failed.");
		$this->assertEquals($testText, linkify_tweet($testText), "Linkify_tweet failed without second parameter"); //default of null should be applied to param 2.
	}
	
	public function testTweetWithALink() {
		$testText = 'The Steve Jobs I Knew -- Some of my remembrances of a great man: http://t.co/3biPrA9D';
		$testEntities = '{"urls":[{"url":"http:\/\/t.co\/3biPrA9D","display_url":"dthin.gs\/rdgAlj","indices":[65,85],"expanded_url":"http:\/\/dthin.gs\/rdgAlj"}],"hashtags":[],"user_mentions":[]}';
		$expected = "The Steve Jobs I Knew -- Some of my remembrances of a great man: <a class=\"\" rel=\"external nofollow\" target=\"_blank\" href=\"http://t.co/3biPrA9D\" title=\"http://dthin.gs/rdgAlj\" >dthin.gs/rdgAlj</a>";

		$this->assertEquals($expected, linkify_tweet($testText, $testEntities));
	}

	public function testTweetWithAUsername() {
		$testText = "@babbanator LOL";
		$testEntities = '{"urls":[],"hashtags":[],"user_mentions":[{"name":"Matthew","indices":[0,11],"screen_name":"babbanator","id_str":"95078052","id":95078052}]}';
		$expected = '<a class="username " rel="external nofollow" target="_blank" href="https://twitter.com/babbanator">@babbanator</a> LOL';

		$this->assertEquals($expected, linkify_tweet($testText, $testEntities));

	}

	public function testTweetWithAHashtag() {
		$testText = 'Clean install time. #Lion';
		$testEntities = '{"urls":[],"hashtags":[{"indices":[20,25],"text":"Lion"}],"user_mentions":[]}';
		$expected = 'Clean install time. <a class="hashtag " rel="external nofollow" target="_blank" href="https://twitter.com/search?q=%23Lion">#Lion</a>';

		$this->assertEquals($expected, linkify_tweet($testText, $testEntities));
	}

	public function testTweetWithATwitterPhoto() {
		$testText = 'Testing the 4S camera. http://t.co/XaHjKZK3';
		$testEntities = '{"media":[{"type":"photo","id_str":"124940717402497025","media_url_https":"https:\/\/p.twimg.com\/Abvg66bCIAEwT34.jpg","display_url":"pic.twitter.com\/XaHjKZK3","indices":[23,43],"expanded_url":"http:\/\/twitter.com\/SteveStreza\/status\/124940717398302720\/photo\/1","sizes":{"small":{"h":255,"w":340,"resize":"fit"},"medium":{"h":450,"w":600,"resize":"fit"},"large":{"h":612,"w":816,"resize":"fit"},"thumb":{"h":150,"w":150,"resize":"crop"}},"id":124940717402497025,"url":"http:\/\/t.co\/XaHjKZK3","media_url":"http:\/\/p.twimg.com\/Abvg66bCIAEwT34.jpg"}],"urls":[],"hashtags":[],"user_mentions":[]}';
		$expected = 'Testing the 4S camera. <a class="twitter-picture " rel="external nofollow" target="_blank" href="http://t.co/XaHjKZK3" title="http://twitter.com/SteveStreza/status/124940717398302720/photo/1" picture-url="http://p.twimg.com/Abvg66bCIAEwT34.jpg" >pic.twitter.com/XaHjKZK3</a>';

		$this->assertEquals($expected, linkify_tweet($testText, $testEntities));
	}

	public function testTweetWithLinkHashtagMentionAndTwitterPhoto() {
		$testText = 'Test tweet with a link http://t.co/Nwx2Jy1 a hashtag #test a mention @andersonshatch and a twitter picture http://t.co/leZZfjt';
		$testEntities = '{"media":[{"type":"photo","id_str":"124981658553290753","media_url_https":"https:\/\/p.twimg.com\/AbwGKAGCEAEAuUC.jpg","display_url":"pic.twitter.com\/leZZfjt","indices":[107,126],"expanded_url":"http:\/\/twitter.com\/kklaven\/status\/124981658549096449\/photo\/1","sizes":{"small":{"h":510,"w":340,"resize":"fit"},"medium":{"h":900,"w":600,"resize":"fit"},"large":{"h":960,"w":640,"resize":"fit"},"thumb":{"h":150,"w":150,"resize":"crop"}},"id":124981658553290753,"url":"http:\/\/t.co\/leZZfjt","media_url":"http:\/\/p.twimg.com\/AbwGKAGCEAEAuUC.jpg"}],"urls":[{"display_url":"google.com","expanded_url":"http:\/\/google.com","indices":[23,42],"url":"http:\/\/t.co\/Nwx2Jy1"}],"hashtags":[{"indices":[53,58],"text":"test"}],"user_mentions":[{"name":"Josh Anderson","id_str":"15135087","indices":[69,84],"screen_name":"andersonshatch","id":15135087}]}';
		$expected = 'Test tweet with a link <a class="" rel="external nofollow" target="_blank" href="http://t.co/Nwx2Jy1" title="http://google.com" >google.com</a> a hashtag <a class="hashtag " rel="external nofollow" target="_blank" href="https://twitter.com/search?q=%23test">#test</a> a mention <a class="username " rel="external nofollow" target="_blank" href="https://twitter.com/andersonshatch">@andersonshatch</a> and a twitter picture <a class="twitter-picture " rel="external nofollow" target="_blank" href="http://t.co/leZZfjt" title="http://twitter.com/kklaven/status/124981658549096449/photo/1" picture-url="http://p.twimg.com/AbwGKAGCEAEAuUC.jpg" >pic.twitter.com/leZZfjt</a>';

		$this->assertEquals($expected, linkify_tweet($testText, $testEntities));
	}

	public function testTweetWithUnicodeCharacterAndLink() {
		$testText = 'Open up your games closet — 16 games now in Google+! http://t.co/l5IzZqV';
		$testEntities = '{"hashtags":[],"urls":[{"display_url":"goo.gl\/b0Kte","url":"http:\/\/t.co\/l5IzZqV","expanded_url":"http:\/\/goo.gl\/b0Kte","indices":[53,72]}],"user_mentions":[]}';
		$expected = 'Open up your games closet — 16 games now in Google+! <a class="" rel="external nofollow" target="_blank" href="http://t.co/l5IzZqV" title="http://goo.gl/b0Kte" >goo.gl/b0Kte</a>';

		$this->assertEquals($expected, linkify_tweet($testText, $testEntities));
	}

	public function testTweetWithProtocolLessLink() {
		$testText = 'Initial plan was to get 8 hours sleep tonight. Then I decided to have a look at cracked.com...';
		$testEntities = '{"urls":[{"expanded_url":null,"indices":[80,91],"url":"cracked.com"}],"hashtags":[],"user_mentions":[]}';
		$expected = 'Initial plan was to get 8 hours sleep tonight. Then I decided to have a look at <a class="" rel="external nofollow" target="_blank" href="http://cracked.com" title="http://cracked.com" >cracked.com</a>...';

		$this->assertEquals($expected, linkify_tweet($testText, $testEntities));
	}

	public function testTweetWithDollarSymbolEntity() {
		$testText = 'ssh you@friendscomputer \'write friend &lt;&lt;&lt;"You want to have lunch? -- $USER"\' # Be sure nobody else sees private communication.';
		$testEntities = '{"hashtags":[],"symbols":[{"text":"USER","indices":[78,83]}],"urls":[],"user_mentions":[]}';
		$expected = 'ssh you@friendscomputer \'write friend &lt;&lt;&lt;"You want to have lunch? -- $USER"\' # Be sure nobody else sees private communication.';

		$this->assertEquals($expected, linkify_tweet($testText, $testEntities));
	}
}
