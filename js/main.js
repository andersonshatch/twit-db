$(document).ready(function() {
	$("body").on(
		{
			mouseenter: function() {
				$(this).children().find(".tweet-actions").css("visibility", "visible");
			},
			mouseleave: function() {
				$(this).children().find(".tweet-actions").css("visibility", "hidden");
			}
		}, '.tweet'
	);
	
	$("#stream").tooltip({
		selector: ".tweet-timestamp",
		placement: "bottom"
	});

	tweetTemplate = $("#tweet-template").html();
	countSpan = $("#tweet-count");
	loading = false;
	moreToLoad = true;

	getResultCount = function(formData) {
		$(countSpan).html('… matching tweets');
		$.ajax("json.php?count-only=true",
			{
				data: formData,
				success: function(data) {
					$(countSpan).html(data.matchingTweets + (data.matchingTweets == 1 ? " matching tweet" : " matching tweets"));
				},
				type: "POST",
				cache: true
			}
		);
	};
	
	getTweets = function(renderLocation, append) {
		var formData = $("#search-form").serialize();
		if(append && $("div.tweet").length > 0) {
			var lastTweet = $("div.tweet:last");
			formData = formData + "&max_id=" + $(lastTweet).attr("data-item-id");
			if($(lastTweet).data("relevance-value"))
				formData = formData + "&relevance=" + $(lastTweet).data("relevance-value");
		}
		if(!loading && moreToLoad) {
			loading = true;
			$.ajax("json.php",
				{
					data: formData,
					success: function(data) {
						if(append)
							$(renderLocation).append(Mustache.to_html(tweetTemplate, data));
						else
							$(renderLocation).html(Mustache.to_html(tweetTemplate, data));
						$(".tweet-timestamp").timeago();
						$("#loadMore").show();
						if(data.tweets.length == 0) {
							$("#loadMore").addClass("disabled");
							$("#loadMore").html("Loaded all");
							moreToLoad = false;
						}
					},
					complete: function () { 
						loading = false;
					},
					type: "POST",
					cache: true
				}
			);
			if(!append)
				getResultCount(formData);
		}
	}

	getTweets("#stream", false);

	$("#search-form").submit(function() {
		$(document.body).animate({scrollTop: 0}, 10);
		moreToLoad = true;
		getTweets("#stream", false);
		return false;
	});

	$("#loadMore").click(function() {
		getTweets("#stream", true);
		return false;
	});

	didScroll = false;
	$(window).scroll(function() {
		didScroll = true;
	});

	setInterval(function() {
		if(didScroll) {
			didScroll = false;
			if(($(document).height() - $(window).height()) - $(window).scrollTop() <= 300) {
				getTweets("#stream", true);
			}
		}
	}, 250);

	$("#logo").click(function() {
		var form = $("#search-form");
		form[0].reset();
		form.trigger("submit");
		return false;
	});

}	
);



