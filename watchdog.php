<?php
/*
 * watchdog.php
 * 
 * Copyright 2016 branko <branko@branko-S551LB>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 * http://sqliteonline.com/
 * http://www.devdungeon.com/content/how-use-sqlite3-php
 */

  # Ping
 
  # Hromadne vlozenie serverov
 
 # Log kontrol / spusteni na konkretny server
 
 # Running multithread
 
 # MOznost nastavit vlastny notify email
 
 # MOznost nastavit vlastnu sms branu

###################################################### 
# SQLite Data file - install parameter in the future
$sqlite_path = "./watchdog.db";
######################################################
 
# What we do?
if($argc == 1) {

	echo "No argument! For help use --help\n";
	die;
	
} elseif($argc >= 2) {

		switch($argv[1]) {
			case '--help':
				echo "Usage: \n";
				die;
			break;
			case '--install':
				WatchDogInstall();
				die;
			break;
			case '--uninstall':
				echo "Are you sure you want to uninstall Watchdog? You lost all your servers in monitoring database and all logs.\n";
				echo "Type DELETE (case-sensitive) to confirm uninstallation: ";
				$delete_confirmation = sread();
				swriteln();
				
				if($delete_confirmation == "DELETE") {
						WatchDogUninstall();
						die;
				} else {
						echo "Interrupted!\n";
				}
				
				die;
				
			break;
			case '--bulkinsert':
				echo "Not implemented yet.\n";
				die;			
			break;
			case '--insert':
					# Check, if is Watchdog installed or not yet
					if(!file_exists($sqlite_path)) die("Cannot continue, Watchdog database not installed. \n");

					echo "Server Hostname: ";
					$hostname = sread();
					echo "Server IP address: ";
					$ipaddress = sread();					
					echo "Port: ";
					$port = sread();
					echo "Expected response HTTP Code (HTTP 200 is default): ";
					$response = sread();					
					echo "Response timeout (default 30): ";
					$responsetimeout = sread();
					swriteln();
					
					$timeout = (!empty($responsetimeout) ? $responsetimeout : 30);
					$params = array('hostname' => $hostname, 'ipaddress' => $ipaddress, 'port' => $port, 'response' => $response, 'timeout' => $timeout);
					WatchDogInsertServer($params);
					exit;
			break;
			case '--remove':
					# Check, if is Watchdog installed or not yet
					if(!file_exists($sqlite_path)) die("Cannot continue, Watchdog database not installed. \n");
					
					echo "Server IP: ";
					$ipaddress = sread();
					swriteln();
					
					WatchDogDeleteServer($ipaddress);
					exit;
			break;
			case '--activate':
				echo "Not implemented yet.\n";
				die;
			break;
			case '--deactivate':
				echo "Not implemented yet.\n";
				die;			
			break;
			case '--run':
				WatchDogRun();
			break;
			default:
				echo "Unkown parameter, use --help\n";
				die;
			break;
		}
	
}

####################### CORE FUNCTIONS #########################
################################################################

# Install Watchdog
function WatchDogInstall() {

		global $sqlite_path;

		# Check, if is Watchdog installed or not yet
		if(file_exists($sqlite_path)) die("Cannot continue, Watchdog database already installed in standard location. \n");
		
		$watchdog_db = new PDO("sqlite:{$sqlite_path}");
		
		$sql_table = "CREATE TABLE IF NOT EXISTS servers(id INTEGER PRIMARY KEY AUTOINCREMENT, hostname VARCHAR(255), ipaddress VARCHAR(255) UNIQUE, port INT(5), response VARCHAR(55), timeout INT(3), active TEXT, last_check DATE);";
		
		try {    
			# Creating table
			$watchdog_db->exec($sql_table);
			
			echo "Congrats! Installation was successful. \n\n!!! NEVER DELETE watchdog.db file! You lost all your monitored servers and logs. !!! \n\nNow use --insert or --bulkinsert to add your servers. If you need more help, use --help\n";
			die();
			
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
	
}

# Insert server to monitor
function WatchDogInsertServer($params) {
	
	global $sqlite_path;
	
	# Let's do some tests for input strings	
	if (!filter_var($params['ipaddress'], FILTER_VALIDATE_IP) === true) {
		die("IP address is invalid. You must enter valid IPv4 or IPv6 address\n");
	} elseif(!filter_var($params['port'], FILTER_VALIDATE_INT) === true || $params['port'] > 65535 || $params['port'] < 80 ) {
		die("Port number is invalid - enter valid port number\n");
	}
	
	$params['hostname'] = trim(htmlspecialchars($params['hostname'], ENT_QUOTES));
	$params['response'] = htmlspecialchars($params['response'], ENT_QUOTES);	
	
	# End Input validation
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	$sql_insert = "INSERT INTO servers (hostname,ipaddress,port,response,timeout,active) VALUES ('".$params['hostname']."', '".$params['ipaddress']."', '".$params['port']."', '".$params['response']."', '".$params['timeout']."', '1')";
	
	try {
			$watchdog_db->exec($sql_insert);
			
			echo "Server inserted and activated for monitoring\n";
			die();
	} catch(PDOException $e) {
			echo $e->getMessage();
	}
	
}
# Delete server from monitor
function WatchDogDeleteServer($ipaddress) {
	
	global $sqlite_path;

	# Let's do some tests for input strings	
	if (!filter_var($ipaddress, FILTER_VALIDATE_IP) === true) {
		die("IP address is invalid. You must enter valid IPv4 or IPv6 address\n");
	}
	
	$sql_count = "SELECT COUNT(id) FROM servers WHERE ipaddress='$ipaddress';";
	$sql_delete = "DELETE FROM servers WHERE ipaddress='$ipaddress';";
	
	try {

		$watchdog_db = new PDO("sqlite:{$sqlite_path}");
		
		$count = $watchdog_db->query($sql_count)->fetch();
		
		if( $count[0] == 0 ) {
				echo "No server with this IP in database.\n";
				die;
		}
		
		$watchdog_db->query($sql_delete);
		
		echo "Server was successfuly deleted from watchog\n";
		die;
		
	} catch(PDOException $e) {
			echo $e->getMessage();
	} 
	
}
# Uninstall Watchdog
function WatchDogUninstall() {

		global $sqlite_path;
		
		if(file_exists($sqlite_path)) {
				unlink($sqlite_path);
		}
		
		echo "Done! All data was permanently deleted. \n";
		die;
	
}
# Run watchdog
function WatchDogRun() {

	global $sqlite_path;
	
	# Get list of all active servers
	$sql_select = "SELECT * FROM servers WHERE active='1' ORDER BY last_check ASC";
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	$result = $watchdog_db->query($sql_select);
	
	foreach($result as $result) {		
		$count = 0;
		$maxTries = 3;
		$send_notification = 0;
		
		$httpcode = GetHttpResponseCode($result['ipaddress'],$result['timeout']);
		
		while(true) {
			
			$expected_response = !empty($result['response']) ? $result['response'] : '200';
			
			if ($httpcode == $expected_response) {
					echo "OK ".$result['ipaddress']."\n";
					
					# Insert log, update last_check
					
					break;
			} else {
					echo "not ok ".$httpcode." ".$count."\n";
					# insert log, update last_check
					
					if($count == $maxTries) { 
						$send_notification = 1;
						break;
					} else {
						sleep(5);
						$count++;
					}
			}
		}
		
		# Send notification on faulty host check
		if($send_notification == 1) {
				echo "Sending notification for ".$result['ipaddress']."\n";
		}
	}
	
}

####### Helpers ##########

	
function sread() {
	$input = fgets(STDIN);
	return rtrim($input);
}

function swrite($text = '') {
	echo $text;
}

function swriteln($text = '') {
	echo $text."\n";
}

function GetHttpResponseCode($url,$timeout) {

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0)");
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
	curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	
	$output = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	return $httpcode;
	
}

function SendSMS($params) {
	
	
}

?>
