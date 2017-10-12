# Nuclear.Hosting Watchdog

Our simple Watchdog monitoring script. watchdog allows you to monitoring HTTP/HTTPS service of your (or any) servers (or domains).

## How it Works

## Requirements

- PHP 5.3+ (CLI)
- PHP Curl extension
- PDO SQLITE
- SQLite3

## How to configure

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
