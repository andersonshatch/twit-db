$(document).ready(function() {

	if($("#timezone-row").length > 0) {
		$("#database-form-row").hide();
	}
	
	$("#timezone").change(
		function() {
			if($(this).prop("selectedIndex") != 0  && $("#timezone option:first").val() == '') {
				$("#timezone option:first").remove();
				$("#database-form-row").fadeIn();
			}
		}
	);		

	$("#database-settings").submit(
		function(event) {
			$(this).children().find("#database-verify").attr("disabled", "disabled");
			$.ajax({
				type: 'POST',
				url: 'lib/setuphelper.php?action=checkdb',
				data: $("#database-settings").serialize(),
				success: function(data){databaseSuccess(data);},
				error: function(xhr){databaseFailure(xhr);}
			});
			event.preventDefault();
		}
	);

	$("#twitter-app-settings").submit(
		function(event) {
			$(this).children().find("#twitter-verify").attr("value", "Processing...");
			var callbackURL = window.location.href.replace("/setup.php", "/lib/setuphelper.php?action=signin");
			$.ajax({
				type: 'POST',
				url: 'lib/setuphelper.php?action=checktwitter',
				data: $("#twitter-app-settings").serialize() + "&oauth_callback=" + encodeURIComponent(callbackURL),
				success: function(data){twitterSuccess(data);},
				error: function(xhr){twitterFailure(xhr);}
			});
			event.preventDefault();
		}
	);
	$("#submit-all").click(
		function(event) {
			var data = $("form").serialize();

			$.ajax({
				type: 'POST',
				url: 'lib/setuphelper.php?action=submit-all',
				data: data,
				success: function(data) {
					$("div#output-box-row").html(data);
					$("div#output-box-row").fadeIn('fast');
				},
				error: function(xhr) {
					$("div#output-box-row").html(xhr.responseText);
					$("div#output-box-row").fadeIn('fast');
				}
			});
			event.preventDefault();
		}
	);

});

$(document).on('click', 'a#signin-button', function(event){popupSigninWindow(event)});
$(document).on('touchstart', 'a#signin-button', function(event){popupSigninWindow(event)});

function popupSigninWindow(event) {
	var url = $("a#signin-button").attr('data-url');
	window.open(url, 'auth', 'width=500, height=600, scrollbars=yes');				
	event.preventDefault();
}

function setUserCredentials(token, secret, screen_name) {
	$("#user-token").attr("value", token);
	$("#user-secret").attr("value", secret);
	$("#user-secret").after('<p class="help-block" id="screen_name">'+screen_name+'</p>');
	$("#other-users").val(screen_name);
	$("#signin-button").slideUp('fast');
	$("#timeline-settings").fadeIn('fast');
}

function twitterSuccess(data) {
	$("#twitter-feedback").slideUp('fast');
	$("#signin-placeholder").html(data);
	$("#twitter-user-row").fadeIn('fast');
	$("#twitter-app-settings input").attr("readonly", "readonly");
	$("#twitter-verify").attr("disabled", "disabled")
		.attr('value', 'Completed')
		.removeClass("btn-primary")
		.addClass("btn-success");
}

function twitterFailure(xhr) {
	$("#twitter-feedback").slideUp('fast',
			function() {
				$(this).html(xhr.responseText);
				$(this).slideDown('fast');
				$("#twitter-verify").attr('value', 'Retry');
			}
	);
}


function databaseSuccess(data) {
	$("#database-feedback").slideUp('fast',
			function() {
				$(this).html(data);
				$(this).slideDown('fast');
			}
	);
	$("#twitter-form-row").fadeIn();
	$("#database-settings input").attr("readonly", "readonly");
	$("#database-verify").attr("disabled", "disabled");
	$("#database-verify").attr('value', 'Connected')
		.removeClass("btn-primary")
		.addClass("btn-success");
	$("#consumer-key").focus();
}
function databaseFailure(xhr) {
	$("#database-feedback").slideUp('fast', 
			function() {
				$(this).html(xhr.responseText);
				$(this).slideDown('fast');
			}
	);
	$("#database-verify").attr('value', 'Retry')
		.removeAttr("disabled");
}
