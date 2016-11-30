<?php
/*
 * watchdog.php
 * 
 * Version: 0.1-03102016
 * 
 * Copyright 2016 Branislav Viest, LiveHost.cz
 * https://branoviest.com | https://livehost.cz
 * info@branoviest.con
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
 * http://edoceo.com/exemplar/php-fork
 * https://oliversmith.io/technology/2011/10/07/speeding-up-php-using-process-forking-for-image-resizing/
 */

 # Ping
 
 # Log kontrol / spusteni na konkretny server
 
 # Pridelit kontaktom len urcite servery
 
 # MOznost nastavit vlastnu sms branu
 
 # Run as daemon (remove lockfile function)
 
 # MySQL check?
 
 # Before install, requirements check (pdo sqlite, curl)
###################################################### 
# SQLite Data file - install parameter in the future
$sqlite_path = "./watchdog.db";
######################################################

$version = "0.1-03102016";

# What we do?
if($argc == 1) {

	echo "No argument! For help use --help\n";
	die;
	
} elseif($argc >= 2) {

		switch($argv[1]) {
			case '--help':
				echo "WatchDog - Monitoring tool for your web and SQL servers
Version: {$version}\n\n";
				echo "Usage: \n
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
	--deactivate-contact - Set active contact as inactuve - no notification wilil be performed for this contact\n
	--help - This help\n\n";
				echo "After you insert contacts and servers into monitoring database, set this scrit with --run parameter to the crontab for every one minute eg.
* * * * * root cd /path/where/is/watchdog/located; php watchdog.php --run\n";
				exit;
			break;
			case '--install':
				WatchDogInstall();
				exit;
			break;
			case '--uninstall':
				echo "Are you sure you want to uninstall Watchdog? You lost all your servers in monitoring database and all logs.\n";
				echo "Type DELETE (case-sensitive) to confirm uninstallation: ";
				$delete_confirmation = sread();
				swriteln();
				
				if($delete_confirmation == "DELETE") {
						WatchDogUninstall();
						exit;
				} else {
						echo "Interrupted!\n";
						exit;
				}
				
				exit;
			break;
			case '--bulkinsert':
				echo "CSV file format is: hostname,ipaddress,port,response_http_code,response_timeout\n";
				echo "Do not use header in your CSV file! \n";
				echo "Example of your csv file: \nserver1.google.com,8.8.8.8,80,200,10\nserver2.google.com,8.8.4.4,80,403,10\n";
				echo "Enter your CSV filename or path: ";
				$filepath = sread();
				swriteln();	
				
				WatchDogBulkInsert($filepath);
				die;
			break;
			case '--add-server':
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
			case '--remove-server':
					# Check, if is Watchdog installed or not yet
					if(!file_exists($sqlite_path)) die("Cannot continue, Watchdog database not installed. \n");
					
					echo "Server IP: ";
					$ipaddress = sread();
					swriteln();
					
					WatchDogDeleteServer($ipaddress);
					exit;
			break;
			case '--activate-server':
				WatchDogListAllServers();
				echo "Which server do you want to set as active? Enter ID:\n";	
				$server_id = sread();
				swriteln();
				
				WatchDogActivateServer($server_id);
				
				exit;
			break;
			case '--deactivate-server':
				WatchDogListAllServers();
				echo "Which server do you want to set as inactive? Enter ID: ";
				$server_id = sread();
				swriteln();
				
				WatchDogDeactivateServer($server_id);
				
				exit;			
			break;
			case '--edit-server':
				WatchDogListAllServers();
				echo "Which server do you want to edit? Enter ID: ";
				$server_id = sread();
				swriteln();
				echo "Leave blank if you do not want to change value.\n";
				# Load default values for this server
				$defaultvalues = LoadServerDefaultValues($server_id);
				
				echo "Server Hostname [{$defaultvalues['hostname']}]: ";
				$hostname = sread();
				echo "Server IP address [{$defaultvalues['ipaddress']}]: ";
				$ipaddress = sread();					
				echo "Port [{$defaultvalues['port']}]: ";
				$port = sread();
				echo "Expected response HTTP Code [{$defaultvalues['response']}]: ";
				$response = sread();					
				echo "Response timeout [{$defaultvalues['timeout']}]: ";
				$responsetimeout = sread();
				echo "Active / In-Active [{$defaultvalues['active']}]: ";
				$active = sread();
				swriteln();
				
				$params = array(
					'hostname' => $hostname,
					'ipaddress' => $ipaddress,
					'port' => $port,
					'response' => $response,
					'timeout' => $responsetimeout,
					'active' => $active
				);
				
				WatchDogEditServer($server_id, $params);
				die;
			break;
			case '--list-servers':
				WatchDogListAllServers();
				exit;
			break;
			case '--run':
				if(!file_exists('.lock')) {
					# Setup lock file
					touch('.lock');
					
					WatchDogRun();
					
					unlink('.lock');
				} else {
					echo "Locked\n";
					exit;
				}
			break;
			case '--add-contact':
				echo "Contact name: ";
				$contact_name = sread();
				echo "Contact SMS phone number (format: 00420xxxxxxxxx): ";
				$contact_phone = sread();
				echo "Contact e-mail: ";
				$contact_email = sread();
				
				swriteln();
				
				WatchDogAddContact(array('contact_name' => $contact_name, 'contact_phone' => $contact_phone, 'contact_email' => $contact_email));
				exit;
				
			break;
			case '--list-contacts':
				WatchDogListAllContacts();
				exit;
			break;
			case '--delete-contact':
				WatchDogListAllContacts();
				echo "Which contact do you want to delete? Enter ID:\n";	
				$contact_id_to_delete = sread();
				
				swriteln();
				
				WatchDogDeleteContact($contact_id_to_delete);
				
				exit;
			break;
			case '--activate-contact':
				WatchDogListAllContacts();
				echo "Which contact do you want to set as active? Enter ID:\n";	
				$contact_id = sread();
				swriteln();
				
				WatchDogActivateContact($contact_id);
				
				exit;
			break;
			case '--deactivate-contact':
				WatchDogListAllContacts();
				echo "Which contact do you want to set as no active? Enter ID:\n";	
				$contact_id = sread();
				swriteln();
				
				WatchDogDeactivateContact($contact_id);
				
				exit;
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
		
		$sql_table = "CREATE TABLE IF NOT EXISTS servers(id INTEGER PRIMARY KEY AUTOINCREMENT, hostname VARCHAR(255), ipaddress VARCHAR(255) UNIQUE, port INT(5), response VARCHAR(55), timeout INT(3), active TEXT, last_check DATE);
					  CREATE TABLE IF NOT EXISTS contacts(id INTEGER PRIMARY KEY AUTOINCREMENT, contact_name VARCHAR(255), phone VARCHAR(255), email VARCHAR(255), active TEXT);";
		
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
	} elseif(!empty($params['response'])) {
		if (!filter_var($params['response'], FILTER_VALIDATE_INT) === true || $params['response'] <200 || $params['response'] >550) {
			die("HTTP Response Code invalid - enter valid HTTP response code between 200 - 550\n");
		}
	} elseif(!filter_var($params['timeout'], FILTER_VALIDATE_INT) === true || $params['timeout'] <10 || $params['timeout'] > 90) {
		die("Correct your response time - allowed is 10 - 90. \n");
	}
	
	$params['hostname'] = trim(htmlspecialchars($params['hostname'], ENT_QUOTES));
	$params['response'] = (!empty($params['response']) ? $params['response'] : 200);
	
	# End Input validation
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	$sql_insert = "INSERT INTO servers (hostname,ipaddress,port,response,timeout,active) VALUES ('".$params['hostname']."', '".$params['ipaddress']."', '".$params['port']."', '".$params['response']."', '".$params['timeout']."', '1')";
	
	try {
			$watchdog_db->exec($sql_insert);
			
			echo "Server inserted and activated for monitoring\n";
			die();
	} catch(PDOException $e) {
			echo $e->getMessage();
			die;
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

	global $sqlite_path,$child;
	
	# Get list of all active servers
	$sql_select = "SELECT * FROM servers WHERE active='1' ORDER BY last_check ASC";
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	$result = $watchdog_db->query($sql_select);
	
	####
	declare(ticks = 1);
	$max=5;
	$child=0;
	pcntl_signal(SIGCHLD, "sig_handler");	
	####
	
	foreach($result as $result) {
		$count = 0;
		$maxTries = 3;
		$send_notification = 0;
		
		################################################################
		while ($child >= $max) {
            sleep(1);
        }
        
        $child++;
        # echo "[+]";
        $pid=pcntl_fork();
        
        if($pid){
		} else {
					// CHILD
					$httpcode = GetHttpResponseCode($result['ipaddress'],$result['timeout'], $result['port']);
					
					while(true) {
						
						$expected_response = !empty($result['response']) ? $result['response'] : '200';
						
						if ($httpcode == $expected_response) {
								# echo "OK ".$result['ipaddress']."\n";
								
								# Insert log, update last_check
								
								break;
						} else {
								# echo "not ok ".$httpcode." ".$count."\n";
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
							# echo "Sending notification for ".$result['ipaddress']."\n";
								
							# Get all active contacts				
							$result_s = $watchdog_db->query("SELECT * FROM contacts WHERE active=1");
							
							$contacts = $result_s->fetchAll();
							
							if(count($contacts) >0) {
								
								foreach ($contacts as $contact) {
									# print_r($contact);
									if(!empty($contact['email'])) {
										# Send Email to all contacts
										SendEmail( array('email' => $contact['email'], 'server' => $result['hostname'], 'ipaddress' => $result['ipaddress']) );
									}
									
									if(!empty($contact['phone'])) {
										# Send SMS to all contacts
										SendSMS(array('phone' => $contact['phone'], 'server' => $result['hostname'], 'ipaddress' => $result['ipaddress']));
									}
									
								}
							}
							
					}
					exit(0);
        }

		
		################################################################
	}
	while($child != 0){
		# echo "($child)";
		sleep(1);
	}

}
# Bulk insert
function WatchDogBulkInsert($filename) {

		global $sqlite_path;

		# Check if source file exists
		if(!file_exists($filename)) {
				echo "Source CSV file does not exists\n";
				die;
		}
		
		# Open file and load data
		$csv = array_map('str_getcsv', file($filename));
				
		foreach($csv as $row) {

			if ( count($row) != 5 ) {
					
					echo "Some of CSV file row does not contains all required fields. Check your CSV file and correct missing values\n";
					die;
			}
			
			# Validate parsed data
			if(empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
				die("All fields are required\n");
			} elseif (!filter_var($row[1], FILTER_VALIDATE_IP) === true) {
				die("IP address is invalid. You must enter valid IPv4 or IPv6 address\n");
			} elseif(!filter_var($row[2], FILTER_VALIDATE_INT) === true || $row[2] > 65535 || $row[2] < 80 ) {
				die("Port number is invalid - enter valid port number\n");
			} elseif(!filter_var($row[3], FILTER_VALIDATE_INT) === true || $row[3] <200 || $row[3] >550) {
				die("HTTP Response Code invalid - enter valid HTTP response code between 200 - 550\n");
			} elseif(!filter_var($row[4], FILTER_VALIDATE_INT) === true || $row[4] <10 || $row[4] > 90) {
				die("Correct your response time - allowed is 10 - 90. \n");
			}
			
			$hostname = trim(htmlspecialchars($row[0], ENT_QUOTES));
		}
		
		# If is everything okay, let's bulk insert

		$watchdog_db = new PDO("sqlite:{$sqlite_path}");
		$sql_insert = "INSERT INTO servers (hostname,ipaddress,port,response,timeout,active) 
					   VALUES (:hostname, :ipaddress, :port, :response, :timeout, 1)";

		$statement = $watchdog_db->prepare($sql_insert);
		
		$statement->bindParam(':hostname', $hostname);
		$statement->bindParam(':ipaddress', $ipaddress);
		$statement->bindParam(':port', $port);
		$statement->bindParam(':response', $response);
		$statement->bindParam(':timeout', $timeout);
		
		foreach ($csv as $row) {
			$hostname = $row[0];
			$ipaddress = $row[1];
			$port = $row[2];
			$response = $row[3];
			$timeout = $row[4];

			$statement->execute();
			
			echo "Inserting server ".$hostname."\n";
			
		}
		
		echo "All is done!\n";
		die; 
}
# Add contact
function WatchDogAddContact($params) {

		global $sqlite_path;
		
		# Validate input
		if(empty($params['contact_name'])) {
				echo "Contact name is required field\n";
				die;
		} elseif( empty($params['contact_phone']) && empty($params['contact_email'])) {
				echo "Contact phone number OR email is required. I cannot notify without it\n";
				die;
		} elseif(!ctype_alnum( str_replace(array(" ","-","_"), '', $params['contact_name'] ) )) {
				echo "Only alfa-num chars and - _ are allow in contact name\n";
				die;
		}
		
		if(!empty($params['contact_email'])) {
				if (filter_var($params['contact_email'], FILTER_VALIDATE_EMAIL) === false) {
					echo "Email address is not valid\n";
					die;
				}
		}
		
		if(!empty($params['contact_phone'])) {
			if(!preg_match("/^((\+420|00420) ?)?\d{3}( |-)?\d{3}( |-)?\d{3}/", $params['contact_phone'])) {
				echo "Invalid phone number format\n";
				die;
			}
		}
		
		# Insert contact into database
		$sql_insert = "INSERT INTO contacts (contact_name, phone, email, active) VALUES ('".$params['contact_name']."', '".$params['contact_phone']."', '".$params['contact_email']."', 1)";
		
		$watchdog_db = new PDO("sqlite:{$sqlite_path}");
		try {
			$watchdog_db->query($sql_insert);
			
			echo "Contact inserted successfuly.\n";
			die;
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		
	
}
# List all contacts from db
function WatchDogListAllContacts() {

	global $sqlite_path;
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	$sql = "SELECT * FROM contacts ORDER BY id ASC";
	
	try {
		$rows = $watchdog_db->query($sql);
		
		echo "ID:\t".str_pad("Name:",20)."\t".str_pad("Email:",20)."\t".str_pad("Phone:",20)."\tActive:\n";
		echo str_repeat("=",90)."\n";
		
		foreach ($rows as $row) {
			echo $row['id']."\t".str_pad($row['contact_name'],20)."\t".str_pad($row['email'],20)."\t".str_pad($row['phone'],20)."\t".$row['active']."\n";
		}		
	} catch(PDOException $e) {
			echo $e->getMessage();
	}
	
}
# Delete contact from db
function WatchDogDeleteContact($contactid) {
	
	global $sqlite_path;
	
	if(empty($contactid) || !filter_var($contactid, FILTER_VALIDATE_INT) === true) {
		echo "Contact ID is not valid.\n";
		exit;
	}
	
	
	$sql = "SELECT count(id) FROM contacts WHERE id='{$contactid}'";
	$sql2 = "DELETE FROM contacts WHERE id='{$contactid}'";
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	
	try {
		$count = $watchdog_db->query($sql);
		
		if($count->fetch()[0] <> 1) {
			echo "Contact ID ".$contactid." not found. Nothing to do.\n";
			exit;
		} else {
			$watchdog_db->query($sql2);
			
			echo "Contact ID ".$contactid." deleted sucessfuly.\n";
			exit;
		}
		
	} catch(PDOException $e) {
			echo $e->getMessage();
			exit;
	}
	
	
}
# Activate inactive contact
function WatchDogActivateContact($contact_id) {

	global $sqlite_path;
	
	
	if(empty($contact_id) || !filter_var($contact_id, FILTER_VALIDATE_INT) === true) {
		echo "Contact ID is not valid.\n";
		exit;
	}
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
		
	$sql = "SELECT count(id) FROM contacts WHERE id='{$contact_id}'";
	$sql2 = "UPDATE contacts SET active=1 WHERE id='{$contact_id}'";
	
	try {
		$count = $watchdog_db->query($sql);
		
		if($count->fetch()[0] <> 1) {
			echo "Contact ID ".$contact_id." not found. Nothing to do.\n";
			exit;
		} else {
			$watchdog_db->query($sql2);
			
			echo "Contact ID ".$contact_id." activated sucessfuly.\n";
			exit;
		}		
	} catch(PDOException $e) {
			echo $e->getMessage();
			exit;
	}

}
# Deactivate active contact
function WatchDogDeactivateContact($contact_id) {

	global $sqlite_path;
	
	if(empty($contact_id) || !filter_var($contact_id, FILTER_VALIDATE_INT) === true) {
		echo "Contact ID is not valid.\n";
		exit;
	}
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
		
	$sql = "SELECT count(id) FROM contacts WHERE id='{$contact_id}'";
	$sql2 = "UPDATE contacts SET active=0 WHERE id='{$contact_id}'";
	
	try {
		$count = $watchdog_db->query($sql);
		
		if($count->fetch()[0] <> 1) {
			echo "Contact ID ".$contact_id." not found. Nothing to do.\n";
			exit;
		} else {
			$watchdog_db->query($sql2);
			
			echo "Contact ID ".$contact_id." deactivated sucessfuly.\n";
			exit;
		}		
	} catch(PDOException $e) {
			echo $e->getMessage();
			exit;
	}
	
}
# List all servers in watchdog
function WatchDogListAllServers() {

	global $sqlite_path;
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	$sql = "SELECT * FROM servers ORDER BY id ASC";
	
	try {
		$rows = $watchdog_db->query($sql);
		
		echo "ID:\t".str_pad("Hostname:",35)."\t".str_pad("IP Address:",10)."\t".str_pad("Port:",5)."\t".str_pad("HTTP response:",8)."\t".str_pad("Timeout:",5)."\tActive:\n";
		echo str_repeat("=",120)."\n";
		
		foreach ($rows as $row) {
			echo $row['id']."\t".str_pad($row['hostname'],35)."\t".str_pad($row['ipaddress'],10)."\t".str_pad($row['port'],5)."\t".str_pad($row['response'],8)."\t".str_pad($row['timeout'],8)."\t".$row['active']."\n";
		}		
	} catch(PDOException $e) {
			echo $e->getMessage();
	}
	
}
# Activate inactive server
function WatchDogActivateServer($server_id) {

	global $sqlite_path;
	
	
	if(empty($server_id) || !filter_var($server_id, FILTER_VALIDATE_INT) === true) {
		echo "Server ID is not valid.\n";
		exit;
	}
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
		
	$sql = "SELECT count(id) FROM servers WHERE id='{$server_id}'";
	$sql2 = "UPDATE servers SET active=1 WHERE id='{$server_id}'";
	
	try {
		$count = $watchdog_db->query($sql);
		
		if($count->fetch()[0] <> 1) {
			echo "Server ID ".$server_id." not found. Nothing to do.\n";
			exit;
		} else {
			$watchdog_db->query($sql2);
			
			echo "Server ID ".$server_id." activated sucessfuly.\n";
			exit;
		}		
	} catch(PDOException $e) {
			echo $e->getMessage();
			exit;
	}

}
# Deactivate active server
function WatchDogDeactivateServer($server_id) {

	global $sqlite_path;
	
	if(empty($server_id) || !filter_var($server_id, FILTER_VALIDATE_INT) === true) {
		echo "Server ID is not valid.\n";
		exit;
	}
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
		
	$sql = "SELECT count(id) FROM servers WHERE id='{$server_id}'";
	$sql2 = "UPDATE servers SET active=0 WHERE id='{$server_id}'";
	
	try {
		$count = $watchdog_db->query($sql);
		
		if($count->fetch()[0] <> 1) {
			echo "Server ID ".$server_id." not found. Nothing to do.\n";
			exit;
		} else {
			$watchdog_db->query($sql2);
			
			echo "Server ID ".$server_id." deactivated sucessfuly.\n";
			exit;
		}		
	} catch(PDOException $e) {
			echo $e->getMessage();
			exit;
	}
	
}
# Edit server
function WatchDogEditServer($server_id, $params) {
	global $sqlite_path;
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	
	# Let's do some tests for input strings
	if(empty($server_id) || !filter_var($server_id, FILTER_VALIDATE_INT) === true) {
		echo "Server ID is not valid.\n";
		exit;
	}
	
	# Check if server with that ID exists
	$sql = "SELECT count(id) FROM servers WHERE id='".$server_id."'";
	$count = $watchdog_db->query($sql);	
	if($count->fetch()[0] <> 1) {
		echo "Server with ID ".$server_id." does not exists\n";
		exit;
	}
	
	if(!empty($params['ipaddress'])) {
		if (!filter_var($params['ipaddress'], FILTER_VALIDATE_IP) === true) {
			die("IP address is invalid. You must enter valid IPv4 or IPv6 address\n");
		}
	} elseif(!empty($params['port'])) {
		if (!filter_var($params['port'], FILTER_VALIDATE_INT) === true || $params['port'] > 65535 || $params['port'] < 80 ) {
			die("Port number is invalid - enter valid port number\n");
		}
	} elseif(!empty($params['response'])) {
		if (!filter_var($params['response'], FILTER_VALIDATE_INT) === true || $params['response'] <200 || $params['response'] >550) {
			die("HTTP Response Code invalid - enter valid HTTP response code between 200 - 550\n");
		}
	} elseif(!empty($params['timeout'])) {
		if (!filter_var($params['timeout'], FILTER_VALIDATE_INT) === true || $params['timeout'] <10 || $params['timeout'] > 90) {
			die("Correct your response time - allowed is 10 - 90. \n");
		}
	} elseif(!empty($params['active'])) {
		if(!filter_var($params['active'], FILTER_VALIDATE_INT) === true || $params['active'] != '0' || $params['active'] != '1') {
			echo "Active is boolean value - 0 = inactive, 1 = active\m";
			exit(1);
		}
	}
	
	# End Input validation

	$defaultvalues = LoadServerDefaultValues($server_id);
	
	# Default value or new?
	$params['hostname'] = (!empty($params['hostname']) ? trim(htmlspecialchars($params['hostname'], ENT_QUOTES)) : $defaultvalues['hostname']);
	$params['response'] = (!empty($params['response']) ? $params['response'] : $defaultvalues['response']);
	$params['ipaddress'] = (!empty($params['ipaddress']) ? $params['ipaddress'] : $defaultvalues['ipaddress']);
	$params['port'] = (!empty($params['port']) ? $params['port'] : $defaultvalues['port']);
	$params['timeout'] = (!empty($params['timeout']) ? $params['timeout'] : $defaultvalues['timeout']);
	$params['active'] = (!empty($params['active']) ? $params['active'] : $defaultvalues['active']);
	
	$sql_insert = "UPDATE servers SET hostname='".$params['hostname']."', ipaddress='".$params['ipaddress']."', port='".$params['port']."', response='".$params['response']."', timeout='".$params['timeout']."', active='".$params['active']."' WHERE id='".$server_id."'";
	
	try {
			$watchdog_db->exec($sql_insert);
			
			echo "Server updated successfuly\n";
			die();
	} catch(PDOException $e) {
			echo $e->getMessage();
			die;
	}
}

####### Helpers ##########

function LoadServerDefaultValues($server_id) {

	global $sqlite_path;

	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	
	# Let's do some tests for input strings
	if(empty($server_id) || !filter_var($server_id, FILTER_VALIDATE_INT) === true) {
		echo "Server ID is not valid.\n";
		exit;
	}
	
	# Load default server values
	$defaultvalues = $watchdog_db->query("SELECT * FROM servers WHERE id='".$server_id."'");
	
	$defaults = $defaultvalues->fetch(PDO::FETCH_ASSOC);
	
	return $defaults;
	
}

function sig_handler($signo){
	  global $child;
	  switch ($signo) {
		case SIGCLD:
		  while( ( $pid = pcntl_wait ( $signo, WNOHANG ) ) > 0 ){
			$signal = pcntl_wexitstatus ($signo);
			$child -= 1;
			# echo "[-]";
		  }
		break;
	  }
}

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

function GetHttpResponseCode($url,$timeout,$port = '80') {

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_PORT , $port);
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

	$tc = $params['phone'];
	
	$text = 'ALERT - Host '.$params['server'].' ('.$params['ipaddress'].') not responding. Date: '.date('Y-m-d H:i:s');
	
	$token = sha1('LiveHostApiex1efvel589'.md5("ex1efvel"));
	$data = base64_encode($text);


	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, 'http://whmcs.livehost.cz/tools/sms.php');
	curl_setopt($ch,CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "token=$token&tc=$tc&data=$data");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);

	curl_close($ch);	
	
}

function SendEmail($params) {

	$headers = "Content-Type: text/plain; charset=utf-8\n";
    $headers .= "From: Watchdog Server ALERT <alert@livehost.cz>\n";
	$headers .= "X-Priority: 1 (Highest)\n";
	$headers .= "X-MSMail-Priority: High\n";
	$headers .= "Importance: High\n";

    $subject = "=?UTF-8?B?".base64_encode('ALERT - SERVER '.$params['server'].' DOWN')."?=";

	$text = 'ALERT - Host '.$params['server'].' ('.$params['ipaddress'].') not responding. Date: '.date('Y-m-d H:i:s');

    mail($params['email'], $subject, $text, $headers);

	
}
?>
