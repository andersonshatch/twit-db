###A Tool to retrieve Twitter timelines & store in MySQL

####Installation
Clone the repository into a web server directory and run
```git submodule update --init
```
to retrieve the submodules.
Visit the directory you cloned into in a web-browser. You should be redirected to setup.php.

Once you have completed the setup process, create a task to run <i>gettweets.php</i> at regular intervals.
(E.g. on OS X the cron task I run is 
 ```
 @hourly /opt/local/bin/php /opt/local/apache2/htdocs/twitter-dev/gettweets.php
 ```
 )

Then, visit the directory again where you can search through the stored tweets using index.php.
