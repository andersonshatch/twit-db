###A Tool to retrieve Twitter timelines & store in MySQL
[![Build Status](https://secure.travis-ci.org/andersonshatch/twit-db.png)](http://travis-ci.org/andersonshatch/twit-db)
####Requirements
- PHP version 5.3 and above
- MySQL version 4.1 and above  

(Automatically determined, only tested on the current PHP & MySQL versions)

####Installation
Clone the repository into a web server directory and run ```git submodule update --init``` to retrieve the submodules.
Visit the directory you cloned into in a web-browser. You should be redirected to setup.php.

Once you have completed the setup process, create a task to run <i>gettweets.php</i> at regular intervals.  
(E.g. on OS X the cron task I run is ```@hourly /opt/local/bin/php /opt/local/apache2/htdocs/twitter-dev/gettweets.php --quiet``` )

Then, visit the directory again where you can search through the stored tweets using index.php.

####Search Tips
You can surround words with quotes to search for that exact combination and prefix them with ```+``` or ```-``` to ensure those words are included, or excluded as necessary.

To search / display mentions (if enabled,) you must use ```@me``` in the username field.
