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

	$("#search-username").typeahead({
		source: function(input, process) {
			$.getJSON("api/users.php", "q=" + input, process);
		},
		valueKey: 'screenName',
		matcher: function(item) {
			return ~item.screenName.toLowerCase().indexOf(this.query.toLowerCase()) || ~item.name.toLowerCase().indexOf(this.query.toLowerCase());
		},
		sorter: function (items) {
			var beginswith = [],
			caseSensitive = [],
			caseInsensitive = [],
			item;

			while (item = items.shift()) {
				if (!item.screenName.toLowerCase().indexOf(this.query.toLowerCase()) || !item.name.toLowerCase().indexOf(this.query.toLowerCase())) beginswith.push(item)
				else if (~item.screenName.indexOf(this.query) || ~item.name.indexOf(this.query)) caseSensitive.push(item)
				else caseInsensitive.push(item)
			}

			return beginswith.concat(caseSensitive, caseInsensitive);
		},
		highlighter: function (item) {
			var query = this.query.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&')
			return item.name.replace(new RegExp('(' + query + ')', 'ig'), function ($1, match) {
					return '<strong>' + match + '</strong>'
				}) + '<br />@' + item.screenName.replace(new RegExp('(' + query + ')', 'ig'), function ($1, match) {
					return '<strong>' + match + '</strong>';
				});
		}
	});

	$.fn.typeahead.Constructor.prototype.render = function(items) {
		//override just to allow using screenName as display value (until more configurable)
		var that = this;
		items = $(items).map(function (i, item) {
			i = $(that.options.item).attr('data-value', item.screenName);
			i.find('a').html(that.highlighter(item));
			return i[0];
		});
		this.$menu.html(items);
		return this;
	};

	tweetTemplate = $("#tweet-template").html();
	countSpan = $("#tweet-count");
	searchForm = $("#search-form");
	loading = false;
	moreToLoad = true;

	getResultCount = function(formData) {
		$(countSpan).html('… matching tweets');
		$.ajax("api/search.php?count-only=true",
			{
				data: formData,
				success: function(data) {
					if($(searchForm).serialize() == formData)
						$(countSpan).html(data.matchingTweets + (data.matchingTweets == 1 ? " matching tweet" : " matching tweets"));
				},
				type: "GET",
				cache: true
			}
		);
	};
	
	getTweets = function(renderLocation, append) {
		var formData = $(searchForm).serialize();
		if(append && $("div.tweet").length > 0) {
			var lastTweet = $("div.tweet:last");
			formData = formData + "&max_id=" + $(lastTweet).attr("data-item-id");
			if($(lastTweet).data("relevance-value"))
				formData = formData + "&relevance=" + $(lastTweet).data("relevance-value");
		}
		if(!loading && moreToLoad) {
			loading = true;
			$.ajax("api/search.php",
				{
					data: formData,
					success: function(data) {
						if(append)
							$(renderLocation).append(Mustache.render(tweetTemplate, data));
						else
							$(renderLocation).html(Mustache.render(tweetTemplate, data));
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
					type: "GET",
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



