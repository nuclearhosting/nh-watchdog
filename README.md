# Nuclear.Hosting Watchdog

Our simple Watchdog monitoring script. watchdog allows you to monitoring HTTP/HTTPS service of your (or any) servers (or domains).

## How it Works

Watchdog is very simple in its concept. Watchdog can monitor multiple servers. All are stored in SQLite database. You can simply insert a new server to monitoring. Watchdog in every its run send HTTP GET request to your server and check if your server respond with specific (you can specify respond HTTP code by yourself) HTTP code. If not, if HTTP code sent by server is different or is reached timeout to respond (timeout you can also specify by yourself), watchdog send notification to all active contacts in database. Contacts you can specify and insert into database by yourself. 

There are two types of notifications, email and SMS.

Watchdog can monitor both HTTP and HTTPS protocols. 

In current version, Watchdog is run from Crontab (every minute or interval you like) and is protected against multiple runs (with lock file). In the feature version there is plan to run watchdog as standalone daemon, running permanently. 

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

## Credentials

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

Copyright 2016 Branislav Viest, Nuclear.Hosting
https://branoviest.com | https://nuclear.hosting
