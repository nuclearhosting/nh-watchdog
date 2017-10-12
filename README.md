# Nuclear.Hosting Watchdog

Our simple Watchdog monitoring script. watchdog allows you to monitoring HTTP/HTTPS service of your (or any) servers (or domains).

## How it Works

## Requirements

- PHP 5.3+ (CLI)
- PHP Curl extension
- PDO SQLITE
- SQLite3

## How to configure

WatchDog uses SQLite database. When you first run / install Watchdog automatically create its SQLite database. Default path for Watchdogs database file is in the current directory where Watchdog script is located in. The default SQLite database file name is "watchdog.db". 

To change default database name or location, just open watchdog.php in your favourite text editor, locate and edit following:
<pre>
  ###################################################### 
  # SQLite Data file - install parameter in the future
  $sqlite_path = "./watchdog.db";
  ######################################################
</pre>

WatchDog also supports SMS notification. In this time, in current watchdog version is not implemented any SMS gateway service. But there is a space where you can implement / write your own code for SMS gateway which you are using. Just located PHP function called ''SendSMS'' (about line 954):
<pre>
function SendSMS($params) {

	# Here you can implement you own SMS gateway

}
</pre>

In this function you can complete your code , eg. sending SMS with HTTP GET to your SMS Gateway API provider or something like that. 

## Usage

Watchdog is regular PHP script. Just type:
<code>
  php watchdog.php --PARAMETER
</code>

List all possible parameters / help:

<pre>
  php watchdog.php --help
  WatchDog - Monitoring tool for your web and SQL servers
  Version: 0.1-03102016

    Usage: 

  	--install - install Watchdog monitoring
  	--uninstall - uninstall Watchdog monitoring
  	--bulkinsert - bulk import servers from CSV
  	--add-server - Add server to monitoring
  	--remove-server - Remove server from monitoring
  	--activate-server - Activate monitoring for inactive server
  	--deactivate-server - Deactivate monitoring for specific server
  	--list-servers - List all servers in monitoring database
  	--edit-server - not implemented yet
  	--run - Start the monitoring process
  	--add-contact - Add contact for notifications
  	--list-contacts - List all contacts in database
  	--delete-contact - Delete contact from notifications
  	--activate-contact - Set inactive contact as active
  	--deactivate-contact - Set active contact as inactuve - no notification wilil be performed for this contact

  	--help - This help

  After you insert contacts and servers into monitoring database, set this scrit with --run parameter to the crontab for every one minute eg.
  * * * * * root cd /path/where/is/watchdog/located; php watchdog.php --run
</pre>
