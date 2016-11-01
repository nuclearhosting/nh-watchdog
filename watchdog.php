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
 
 # Log kontrol / spusteni na konkretny server
 
 # Running multithread
 
 # Pridelit kontaktom len urcite servery
 
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
				echo "CSV file format is: hostname,ipaddress,port,response_http_code,response_timeout\n";
				echo "Do not use header in your CSV file! \n";
				echo "Example of your csv file: \nserver1.google.com,8.8.8.8,80,200,10\nserver2.google.com,8.8.4.4,80,403,10\n";
				echo "Enter your CSV filename or path: ";
				$filepath = sread();
				swriteln();	
				
				WatchDogBulkInsert($filepath);
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
			case '--list-servers':
				echo "Not implemented yet.\n";
				die;						
			break;
			case '--run':
				WatchDogRun();
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
	} elseif(!filter_var($params['response'], FILTER_VALIDATE_INT) === true || $params['response'] <200 || $params['response'] >550) {
		die("HTTP Response Code invalid - enter valid HTTP response code between 200 - 550\n");
	} elseif(!filter_var($params['timeout'], FILTER_VALIDATE_INT) === true || $params['timeout'] <10 || $params['timeout'] > 90) {
		die("Correct your response time - allowed is 10 - 90. \n");
	}
	
	$params['hostname'] = trim(htmlspecialchars($params['hostname'], ENT_QUOTES));	
	
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

	global $sqlite_path;
	
	# Get list of all active servers
	$sql_select = "SELECT * FROM servers WHERE active='1' ORDER BY last_check ASC";
	
	$watchdog_db = new PDO("sqlite:{$sqlite_path}");
	$result = $watchdog_db->query($sql_select);
	
	####################################################################
	$child_list = 0;

	declare(ticks = 1);
	pcntl_signal(SIGCHLD, 'sig_handler');	
	####################################################################
	
	foreach($result as $result) {		
		$count = 0;
		$maxTries = 3;
		$send_notification = 0;
		
		################################################################
		 // Fork
			$pid = pcntl_fork();
			switch ($pid) {
			case -1: // Error
				die('Fork failed, your system is b0rked!');
				break;
			case 0: // Child
				// Remove Signal Handlers in Child
				pcntl_signal(SIGCHLD,SIG_DFL);
				###
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
				###
				exit(0);
				break;
			default: // Parent
				echo "run: $child_list processes\n";
				if ($child_list >= 10) {
					// Just wait for one to die
					pcntl_wait($x);
					$child_list--;
				}
				$child_list++;
				break;
			}
		################################################################
		
	/*	$httpcode = GetHttpResponseCode($result['ipaddress'],$result['timeout']);
		
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
		*/
		# Send notification on faulty host check
		if($send_notification == 1) {
				echo "Sending notification for ".$result['ipaddress']."\n";
					
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
							echo "Toto je SMS alert na vypadok";
							# SendSMS(array('phone' => $contact['phone'], 'server' => $result['hostname'], 'ipaddress' => $result['ipaddress']);
						}
						
					}
				}
				
		}
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
		
		echo "ID:\tName:\tEmail:\tPhone:\tActive:\n";
		
		foreach ($rows as $row) {
			echo $row['id']."\t".$row['contact_name']."\t".$row['email']."\t".$row['phone']."\t".$row['active']."\n";
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
####### Helpers ##########

function sig_handler($sig)	{
		global $child_list;
		switch ($sig) {
		case SIGCHLD:
			$child_list--;
			while( ( $pid = pcntl_wait ( $sig, WNOHANG ) ) > 0 ){
				$x = pcntl_wexitstatus ( $sig );
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
