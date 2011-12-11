$(document).ready(function() {
	$(".tweet").hover(
		function() {
			$(this).children().find(".tweet-actions").css("visibility", "visible");
		},
		function() {
			$(this).children().find(".tweet-actions").css("visibility", "hidden");
		}
	);
	
	$("img").lazyload({placeholder: 'css/bird_48_grey.png'});
	$(".tweet-timestamp").twipsy({
		live: true,
		placement: "below"
	}).timeago();
}	
);

