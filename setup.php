<?php
header('Cache-Control: no-cache, max-age=0');
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="bootstrap/bootstrap.min.css" />
<title>Twit-DB setup</title>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js"></script>
<script type="text/javascript" src="js/setup.js"></script>
</head>
<body>
<?php
	if( !file_exists('bootstrap/bootstrap.min.css') ){
			echo "Bootstrap files are not present. Please run <code>git submodule update --init</code> from this directory to retrieve them, and then refresh.";
			echo "</body></html>";
			exit;
	}
?>
<div class="container">
	<div class="content">
		<div class="page-header">
			<h1>Twi-DB setup</h1>
		</div>
		<?php 
		if(file_exists("config.php")){
			if(is_readable("config.php")){
				//exists and readable
				echo "<div class=\"alert-message warning\">Warning: Config file already exists, completing setup will replace it with new settings.</div>";
			}else{
				//exists, not readable
				echo "<div class=\"alert-message error\">Error: Config file already exists, but isn't readable. Make it readable and then click <a href=\"index.php\">here</a>.</div>";
			}
		}
		?>
		<div class="row offset3" id="database-form-row">
			<div class="span4">
			<h3>MySQL Settings </h3>
				<p>
				You'll need to create a new database for this application to use. phpMyAdmin is the easiest way, but <code>create database <i>[name-here]</i>;</code> in a mysql prompt would suffice.
				</p>
			</div>
			<div class="span4">
				<form class="form-stacked" id="database-settings">
				    <fieldset>
					    <div class="clearfix">
						    <label for="host">Database Host</label>
						    <input type="text" name="db_host" id="db_host">
						    <span class="help-inline">Typically localhost</span>
					    </div>
					    <div class="clearfix">
						    <label for="username">Database Username</label>
						    <input type="text" name="db_uname" id="db_uname">
					    </div>
					    <div class="clearfix">
						    <label for="password">Database Password</label>
						    <input type="password" name="db_pass" id="db_pass">
					    </div>
					    <div class="clearfix">
						    <label for="name">Database Name</label>
						    <input type="text" name="db_name" id="db_name">
						    <span class="help-inline">Name of the database you created</span>
					    </div>
					    <input type="submit" class="btn primary pull-right" id="database-verify" value="Verify">
				    </fieldset>
				</form>
			</div>
		</div>
		<div class="row offset3" id="database-feedback" style="display: none;">
		
		</div>

		<div class="row offset3" id="twitter-form-row" style="display: none;">
			<div class="span4">
			<h3>Twitter App Credentials</h3>
				<p>
				Create a twitter app at <a href="https://dev.twitter.com/apps/new" target="_blank">dev.twitter.com</a> if you don't already have one.<br />
				The app should have at least <b>Read and Write</b> access.
				</p>
			</div>
			<div class="span4">
			<form class="form-stacked" id="twitter-app-settings">
				<fieldset>
				    <div class="clearfix">
					    <label for="consumer-key">Consumer Key</label>
					    <input type="text" name="consumer-key" id="consumer-key">
				    </div>
				    <div class="clearfix">
					    <label for="consumer-secret">Consumer Secret</label>
					    <input type="text" name="consumer-secret" id="consumer-secret">
				    </div>
				    <input type="submit" class="btn primary pull-right" id="twitter-verify" value="Verify">
				</fieldset>
			</div>
			</form>
		</div>
		<div class="row offset3" id="twitter-feedback" style="display: none;">
		</div>
		<div class="row offset3" id="twitter-user-row" style="display: none;">
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
						<div class="clearfix">
							<label for="user-token">User Token</label>
							<input type="text" name="user-token" id="user-token" readonly="readonly">
						</div>
						<div class="clearfix">
							<label for="user-secret">User Secret</label>
							<input type="text" name="user-secret" id="user-secret" readonly="readonly">
						</div>
					</fieldset>
				</form>
			</div>
		</div>
		<div class="row offset3" id="timeline-settings" style="display: none;">
			<div class="span4">
				<h3>Timeline Settings</h3>
				<p>
				Select the timelines you want to be saved by this tool, and optionally list any additional accounts to store.
				</p>
			</div>
			<div class="span4">
				<form class="form-stacked" id="timeline-settings">
					<fieldset>
						<div class="clearfix">
							<ul class="inputs-list">
								<li>
									<label>
										<input type="checkbox" name="home-timeline" id="home-timeline" checked="checked" readonly="readonly">
										<span>Home</span>
									</label>
								</li>
								<li>
									<label>
										<input type="checkbox" name="mentions-timeline" id="mentions-timeline">
										<span>Mentions</span>
									</label>
								</li>
							</ul>
						</div>
						<div class="clearfix">
							<label for="other-users">Other Users</label>
							<input type="text" name="other-users" id="other-users">
							<p>
							Enter a comma seperated list of users to save in addition to the selected timelines. (Optional)<br/>
							e.g. <code><i>bs,bbcnews,twitter</i></code><br />
							Each user in this list will be saved to a seperate table and will only be searchable when specifing the user explictly. As many tweets as possible will be retrieved for each user. (Only most recent 3200 tweets if user has more.)
						</p>
						</div>
						<input type="submit" class="btn pull-right" id="submit-all" value="Save">
					</fieldset>
				<form>
			</div>
		</div>
		<div class="row offset3" id="output-box-row" style="display: none;">
		</div>
	</div><!--content-->
</div><!--container-->

</body>
</html>
