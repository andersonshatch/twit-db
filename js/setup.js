$(document).ready(function() {

	$("form#database-settings").submit(
		function(event){
			$.ajax({
				type: 'POST',
				url: 'setuphelper.php?action=checkdb',
				data: $("form#database-settings").serialize(),
				success: function(data){databaseSuccess(data);},
				error: function(xhr){databaseFailure(xhr);}
			});
			event.preventDefault();
		}
	);

	$("form#twitter-app-settings").submit(
		function(event){
			$(this).children().find("input#twitter-verify").attr("value", "Processing...");
			var callbackURL = window.location.href.replace("/setup.php", "/setuphelper.php?action=signin");
			$.ajax({
				type: 'POST',
				url: 'setuphelper.php?action=checktwitter',
				data: $("form#twitter-app-settings").serialize() + "&oauth_callback=" + encodeURIComponent(callbackURL),
				success: function(data){twitterSuccess(data);},
				error: function(xhr){twitterFailure(xhr);}
			});
			event.preventDefault();
		}
	);
	$("input#submit-all").click(
		function(event){
			var data = $("form").serialize();

			$.ajax({
				type: 'POST',
				url: 'setuphelper.php?action=submit-all',
				data: data,
				success: function(data){
					$("div#output-box-row").html(data);
					$("div#output-box-row").fadeIn('fast');
				},
				error: function(xhr){
					$("div#output-box-row").html(xhr.responseText);
					$("div#output-box-row").fadeIn('fast');
				}
			});
			event.preventDefault();
		}
	);

});

$("a#signin-button").live('click', function(event){popupSigninWindow(event)});
$("a#signin-button").live('touchstart', function(event){popupSigninWindow(event)});

function popupSigninWindow(event){
			var url = $("a#signin-button").attr('data-url');
			window.open(url, 'auth', 'width=500, height=600, scrollbars=yes');				
			event.preventDefault();
}

function setUserCredentials(token, secret, screen_name){
	$("input#user-token").attr("value", token);
	$("input#user-secret").attr("value", secret);
	$("input#user-secret").after('<span class="help-inline" id="screen_name">'+screen_name+'</span>');
	$("a#signin-button").slideUp('fast');
	$("div#timeline-settings").fadeIn('fast');
}

function twitterSuccess(data){
	$("div#twitter-feedback").slideUp('fast');
	$("span#signin-placeholder").html(data);
	$("div#twitter-user-row").fadeIn('fast');
	$("form#twitter-app-settings input").attr("readonly", "readonly");
	var button = $("input#twitter-verify");
	button.attr("disabled", "disabled");
	button.attr('value', 'Completed');
	button.removeClass("primary");
	button.addClass("success");
}

function twitterFailure(xhr){
	$("div#twitter-feedback").slideUp('fast',
			function(){
				$(this).html(xhr.responseText);
				$(this).slideDown('fast');
				$("input#twitter-verify").attr('value', 'Retry');
			}
	);
}


function databaseSuccess(data){
	$("div#database-feedback").slideUp('fast',
			function(){
				$(this).html(data);
				$(this).slideDown('fast');
			}
	);
	$("#twitter-form-row").fadeIn();
	$("form#database-settings input").attr("readonly", "readonly");
	$("input#database-verify").attr("disabled", "disabled");
	var button = $("input#database-verify");
	button.attr('value', 'Connected');
	button.removeClass("primary");
	button.addClass("success");
	$("input#consumer-key").focus();
}
function databaseFailure(xhr){
	$("div#database-feedback").slideUp('fast', 
			function(){
				$(this).html(xhr.responseText);
				$(this).slideDown('fast');
			}
	);
	$("input#database-verify").attr('value', 'Retry');
}
