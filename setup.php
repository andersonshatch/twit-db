<?php
header('Cache-Control: no-cache, max-age=0');
chdir(dirname(__FILE__));
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="bootstrap/docs/assets/css/bootstrap.css" />
<title>Twit-DB setup</title>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="js/setup.js"></script>
</head>
<body>
<?php
	if(!file_exists('bootstrap/docs/')) {
			echo "Bootstrap files are not present. Please run <code>git submodule update --init</code> from this directory to retrieve them, and then refresh.";
			echo "</body></html>";
			exit;
	}
?>
<div class="container">
	<div class="content">
		<div class="page-header">
			<h1>Twit-DB setup</h1>
		</div>
		<?php 
		if(file_exists("config.php")) {
			if(is_readable("config.php")) {
				//exists and readable
				echo "<div class=\"alert alert-warning\">Warning: Config file already exists, completing setup will replace it with new settings.</div>";
			} else {
				//exists, not readable
				echo "<div class=\"alert alert-error\">Error: Config file already exists, but isn't readable. Make it readable and then click <a href=\"index.php\">here</a>.</div>";
			}
		}
		if(!ini_get('date.timezone')) { ?>
		    <div class="row" id="timezone-row">
			    <div class="span4">
				<h3>PHP Settings</h3>
					<p>
					Select your timezone.
					</p>
			    </div>
			    <div class="span4">
			    	<form class="form-stacked" id="timezone-settings">
						<fieldset>
							<label for="timezone">Timezone</label>
							<select class="span4" name="timezone" id="timezone">
								<option></option>
								<?php
									$timezones = DateTimeZone::listIdentifiers();
									foreach($timezones as $timezone) {
										echo "<option>$timezone</option>";
									}
								?>
							</select>
						</fieldset>
					</form>
			    </div>
		    </div>
		<?php }	?>
		<div class="row" id="database-form-row">
			<div class="span4">
			<h3>MySQL Settings </h3>
				<p>
				You'll need to create a new database for this application to use. phpMyAdmin is the easiest way, but <code>create database <i>[name-here]</i>;</code> in a mysql prompt would suffice.
				</p>
			</div>
			<div class="span4">
				<form class="form-stacked" id="database-settings">
				    <fieldset>
					    <div class="">
						    <label for="db_host">Database Host</label>
						    <input type="text" name="db_host" id="db_host" autocapitalize="none">
						    <p class="help-block">Typically localhost</p>
					    </div>
					    <div class="">
						    <label for="db_uname">Database Username</label>
						    <input type="text" name="db_uname" id="db_uname" autocapitalize="none">
					    </div>
					    <div class="">
						    <label for="db_pass">Database Password</label>
						    <input type="password" name="db_pass" id="db_pass">
					    </div>
					    <div class="">
						    <label for="db_name">Database Name</label>
						    <input type="text" name="db_name" id="db_name" autocapitalize="none">
						    <p class="help-block">Name of the database you created</p>
					    </div>
					    <input type="submit" class="btn btn-primary pull-right" id="database-verify" value="Verify">
				    </fieldset>
				</form>
			</div>
		</div>
		<div class="row" id="database-feedback" style="display: none;">
		</div>
		<div class="row" id="twitter-form-row" style="display: none;">
			<div class="span4">
			<h3>Twitter App Credentials</h3>
				<p>
				Create a twitter app at <a href="https://apps.twitter.com/" target="_blank">apps.twitter.com</a> if you don't already have one.<br />
				The app should have at least <b>Read and Write</b> access.
				</p>
			</div>
			<div class="span4">
			<form class="form-stacked" id="twitter-app-settings">
				<fieldset>
				    <div class="">
					    <label for="consumer-key">Consumer Key</label>
					    <input type="text" name="consumer-key" id="consumer-key">
				    </div>
				    <div class="">
					    <label for="consumer-secret">Consumer Secret</label>
					    <input type="text" name="consumer-secret" id="consumer-secret">
				    </div>
				    <input type="submit" class="btn btn-primary pull-right" id="twitter-verify" value="Verify">
				</fieldset>
			</form>
			</div>
		</div>
		<div class="" id="twitter-feedback" style="display: none;">
		</div>
		<div class="row" id="twitter-user-row" style="display: none;">
			<div class="span4">
				<h3>User credentials</h3>
				<p>
				Use the signin button below to connect a twitter account.
				(nothing will be posted to your account by this tool).
				</p>
				<span id="signin-placeholder"></span>
			</div>
			<div class="span4">
				<form class="form-stacked" id="twitter-user-settings">
					<fieldset>
						<div class="">
							<label for="user-token">User Token</label>
							<input type="text" name="user-token" id="user-token" readonly="readonly">
						</div>
						<div class="">
							<label for="user-secret">User Secret</label>
							<input type="text" name="user-secret" id="user-secret" readonly="readonly">
						</div>
					</fieldset>
				</form>
			</div>
		</div>
		<div class="row" id="timeline-settings" style="display: none;">
			<div class="span4">
				<h3>Timeline Settings</h3>
				<p>
				Select the timelines you want to be saved by this tool, and optionally list any additional accounts/lists to store.
				If you want to store lists, enter them in the form username/list-name, e.g.: twitter/engineering
				</p>
			</div>
			<div class="span4">
				<form class="form-stacked" id="timeline-settings">
					<fieldset>
						<div class="control-group">
							<div class="inputs-list">
								<label class="checkbox">
									<input type="checkbox" name="home-timeline" id="home-timeline" checked="checked" readonly="readonly">
									Home
								</label>
								<label class="checkbox">
									<input type="checkbox" name="mentions-timeline" id="mentions-timeline">
									Mentions
								</label>
								<label>
									<input type="checkbox" name="favorites-timeline" id="favorites-timeline">
									Favo(u)rites
								</label>
							</div>
						</div>
						<div class="">
							<label for="other-users">Other Users</label>
							<input type="text" name="other-users" id="other-users">
							<p>
							Enter a comma seperated list of users to save in addition to the selected timelines. (Optional)<br/>
							e.g. <code><i>bs,bbcnews,twitter,roosterteeth/staff</i></code><br />
							As many tweets as possible will be retrieved for each user/list. (Only most recent ~3200 tweets for users, ~800 for lists)
						</p>
						</div>
						<input type="submit" class="btn btn-primary pull-right" id="submit-all" value="Save">
					</fieldset>
				</form>
			</div>
		</div>
		<div class="" id="output-box-row" style="display: none;">
		</div>
	</div><!--content-->
</div><!--container-->

</body>
</html>
