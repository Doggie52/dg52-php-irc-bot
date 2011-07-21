<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Main class holding the main bot class and related functions
	 */
	
	/**
	 * IRCBot class.
	 */
	class IRCBot
	{
		// This is going to hold the data received from the socket
		private $rawData;
		
		// This is going to hold all of the messages from both server and client
		private $parsedData = array();
		
		// This holds the plugin objects
		private $plugins;
		
		/**
		 * Constructs the bot by opening the server connection and CLI interface, logging the bot in and importing list of administrators.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct()
		{		
			// Our TCP/IP connection
			global $socket;
			$socket = fsockopen(SERVER_IP, SERVER_PORT);
			
			// Our CLI interface
			global $cli;
			$cli = STDIN;
			
			// Set non-blocking mode on both sockets
			stream_set_blocking($socket, 0);
			stream_set_blocking($cli, 0);
			
			// Include functions
			include("core/class_func.php");
			include("core/class_user.php");
			include("core/class_pluginhandler.php");

			 // Replaces %date% with the date in the form yyyymmdd
			$newpath = preg_replace("/%date%/", @date('Ymd'), LOG_PATH);
			// Clears the logfile if LOG_APPEND is FALSE and if the file already exists
			if(!LOG_APPEND && file_exists($newpath))
			{
				if(unlink($newpath))
				{
					debug_message("Log cleared!");
				}
			}
			
			// Print header
			$this->printHeader();
			
			pluginHandler::loadPlugins();
			// Trigger plugin load
			pluginHandler::triggerEvent("load");
			// When the loop is broken, we have been greeted
			pluginHandler::triggerEvent("connect");
			
			// Stores list of administrators
			global $users;
			$users = $this->reloadUsers();
			
			// Initializes the main bot workhorse
			$this->main();
			
			// Closes the socket
			fclose($socket) or die('Unable to close the socket!');
		}
		
		/**
		 * This is the workhorse function, grabs the data from the server and displays on the browser if DEBUG_OUTPUT is true.
		 *
		 * @access private
		 * @return void
		 */
		private function main()
		{
			// Fetch the socket
			global $socket;
			// Fetch the CLI
			global $cli;
			
			while(!feof($socket))
			{
				// If something has been typed
				if($cliinput = fgets($cli))
				{
					echo "CLI INPUT: ".$cliinput;
				}
				
				// If there is something new in the socket (prevents over-use of resources)
				if($this->rawData = fgets($socket))
				{				
					// If the debug output is turned on, spew out all data received from server
					if(DEBUG_OUTPUT)
					{
						echo nl2br($this->rawData);
					}
					flush();
					
					$this->parsedData = $this->parseRawCommand($this->rawData);
					
					if($this->parsedData[0] == "PING")
					{
						// Plays ping-pong with the server to stay connected
						send_data("PONG", $this->parsedData[1]);
						if(!SUPPRESS_PING)
						{
							debug_message("PONG was sent.");
						}
					}
					
					// If the message is a command
					if(@strtolower($this->parsedData['command'][0][0]) == COMMAND_PREFIX)
					{
						$this->parsedData['fullCommand'] = substr($this->parsedData['fullCommand'], 1);
						pluginHandler::triggerEvent("command", $this->parsedData);
						
						if($this->parsedData['messageType'] == "PRIVATE")
						{
							// Separate handling for the !quit command
							if($this->parsedData['authLevel'] == 1 && strtolower(substr($this->parsedData['command'][0], 1)) == "quit")
							{
								pluginHandler::triggerEvent("disconnect");
								pluginHandler::$plugins['ServerActions']->quit();
								break;
							}
						}
					}
					// If the message is a message
					else
					{
						
					}
				}
			}
		}
		
		/**
		 * Prints the bot's header with useful information and some nice ASCII text.
		 *
		 * @access private
		 * @return void
		 */
		private function printHeader()
		{
			$svnrev = getLatestRev("http://dg52-php-irc-bot.googlecode.com/svn/trunk/");
			$info = "
	     _  ___ ___ ___ 
	  __| |/ __| __|_  )
	 / _` | (_ |__ \/ / 
	 \__,_|\___|___/___|
	                    
	  ___ _  _ ___   ___ ___  ___ 
	 | _ \ || | _ \ |_ _| _ \/ __|
	 |  _/ __ |  _/  | ||   / (__ 
	 |_| |_||_|_|   |___|_|_\\___|
	                              
	  ___      _   
	 | _ ) ___| |_ 
	 | _ \/ _ \  _|
	 |___/\___/\__|
	 
	 dG52 PHP IRC Bot
	   Author: Douglas Stridsberg
	   Email: doggie52@gmail.com
	   URL: www.douglasstridsberg.com
	   Latest (online) revision: r".$svnrev."
	     (if you have an earlier revision than this, please update!)
	 
	 Any issues, questions or feedback should be redirected
	 to the following URL.
	 
	 http://code.google.com/p/dg52-php-irc-bot
	
				\n";
			if(GUI)
			{
				echo(nl2br($info));
			}
			else
			{
				echo($info);
			}
		}
		
		/**
		 * Parses the raw commands sent by the server and splits them up into different parts, storing them in the $ex array for future use.
		 *
		 * @access private
		 * @param string $data The raw data sent by the socket
		 * @return array $ex The parsed raw command, split into an array
		 */
		private function parseRawCommand($data)
		{
			// Explodes the raw data into an initial array
			$ex					= explode(" ", $data);
			// Get length of everything before command including last space
			$identlength		= strlen($ex[0]." ".(isset($ex[1]) ? $ex[1] : "")." ".(isset($ex[2]) ? $ex[2] : "")." ");
			// Retain all that is in $data after $identlength characters with replaced chr(10)'s and chr(13)'s and minus the first ':'
			$rawcommand			= substr($data, $identlength);
			$ex['fullCommand']	= substr(str_replace(array(chr(10), chr(13)), '', $rawcommand), 1);
			// Split the commandstring up into a second array with words
			$ex['command']		= explode(" ", $ex['fullCommand']);
			// The username!hostname of the sender (don't include the first ':' - start from 1)
			$ex['ident']		= substr($ex[0], 1);
			// Only the username of the sender (one step extra because only that before the ! wants to be parsed)
			$hostlength			= strlen(strstr($ex[0], '!'));
			$ex['sender']		= substr($ex[0], 1, -$hostlength);
			// The receiver of the sent message (either the channelname or the bot's nickname)
			$ex['receiver']		= $ex[2];
			// Interpret the type of message received ("PRIVATE" or "CHANNEL") depending on the receiver
			$ex['messageType']	= $this->interpretMessage($ex['receiver']);
			// Get whether the user is authenticated
			$ex['authLevel'] 	= $this->isAuthenticated($ex['ident']);
			
			return $ex;
		}
		
		/**
		 * (re)Loads the array of administrators
		 *
		 * @access private
		 * @return array $users The array of administrators
		 */
		private function reloadUsers()
		{
			// Open the users.inc file
			$file = fopen(USERS_PATH, "r");
			// Turn the hostnames into lowercase (does not compromise security, as hostnames are unique anyway)
			$userlist = strtolower(fread($file, filesize(USERS_PATH)));
			fclose($file);
			// Split each line into separate entry in the returned array
			$users = explode("\n", $userlist);
			debug_message("The list of administrators was successfully loaded into the system!");
			return $users;
		}
		
		/**
		 * Interprets the receiver of a message sent and returns whether it is one from a user (a private message) or one sent in the channel.
		 * 
		 * @access private
		 * @param string $message The message to be interpretted
		 * @return string $type The type of message sent ("PRIVATE" or "CHANNEL")
		 */
		private function interpretMessage($message)
		{
			// Regular expressions to match "#channel"
			$regex = '/(#)((?:[a-z][a-z]+))/is';
			
			// If the receiver includes a channelname
			if(preg_match($regex, $message))
			{
				// ... it was sent to the channel
				$type = "CHANNEL";
				return $type;
			}
			// Or if the sent message's receiver is the bots nickname
			elseif($message == BOT_NICKNAME)
			{
				// ... it is a private message
				$type = "PRIVATE";
				return $type;
			}
		}
		
		/**
		 * Checks if sender of message is in the list of authenticated users. (username!hostname)
		 * 
		 * @access private
		 * @param string $ident The username!hostname of the user we want to check
		 * @return boolean $authenticated TRUE for an authenticated user, FALSE for one which isn't
		 */
		private function isAuthenticated($ident)
		{
			// Fetch the userlist array
			global $users;
			
			// If the lower-case ident is found in the userlist array, return true
			$ident = strtolower($ident);
			if(in_array($ident, $users))
			{
				debug_message("User ($ident) is authenticated.");
				$authenticated = true;
			}
			else
			{
			    debug_message("User ($ident) is not authenticated.");
				$authenticated = false;
			}
			
			return $authenticated;
		}
	
	}
?>