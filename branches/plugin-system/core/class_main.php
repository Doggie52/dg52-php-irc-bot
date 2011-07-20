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
		
		// This is going to hold our responses
		private $response;
		
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
			$this->print_header();
			
			// Instantiate plugin object and trigger the load
			$this->pluginHandler = new PluginHandler;
			$this->pluginHandler->triggerEvent("load");
			// When the loop is broken, we have been greeted
			$this->pluginHandler->triggerEvent("connect");
			
			// Stores list of administrators
			global $users;
			$users = reload_users();
			
			// Include responses
			$this->response = reload_speech();
			
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
					
					$this->parsedData = $this->parse_raw_command($this->rawData);
					
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
						// Distinguish between channelmsg and privmsg - the latter needs 'username' as the $from parameter whilst the former needs to include information about the sender
						if($this->parsedData['type'] == "CHANNEL")
						{
							$this->pluginHandler->triggerEvent("command",
								substr($this->parsedData['fullcommand'], 1),
								$this->parsedData['type'],
								$this->parsedData['username'],
								$this->parsedData['receiver'],
								$this->parsedData['authlevel']);
						}
						elseif($this->parsedData['type'] == "PRIVATE")
						{
							// Separate handling for the !quit command
							if($this->parsedData['authlevel'] == 1 && strtolower(substr($this->parsedData['command'][0], 1)) == "quit")
							{
								$this->pluginHandler->triggerEvent("disconnect");
								$this->pluginHandler->plugins['ServerActions']->quit();
								break;
							}
							
							$this->pluginHandler->triggerEvent("command",
								substr($this->parsedData['fullcommand'], 1),
								$this->parsedData['type'],
								$this->parsedData['username'],
								NULL,
								$this->parsedData['authlevel']);
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
		 * Gets the latest revision of an SVN repository with a general HTML output.
		 * 
		 * @access private
		 * @param string $site The URI of the repository (with http://)
		 * @return string $revision The revision number extracted
		 */
		private function get_latest_rev($site)
		{
			$raw = file_get_contents($site);
			$regex = "/(Revision)(\\s+)(\\d+)(:)/is";
			preg_match_all($regex, $raw, $match);
			$revision = $match[3][0];
			
			return $revision;
		}
		
		/**
		 * Prints the bot's header with useful information and some nice ASCII text.
		 *
		 * @access private
		 * @return void
		 */
		private function print_header()
		{
			$svnrev = $this->get_latest_rev("http://dg52-php-irc-bot.googlecode.com/svn/trunk/");
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
		private function parse_raw_command($data)
		{
			// Explodes the raw data into an initial array
			$ex					= explode(" ", $data);
			// Get length of everything before command including last space
			$identlength		= strlen($ex[0]." ".(isset($ex[1]) ? $ex[1] : "")." ".(isset($ex[2]) ? $ex[2] : "")." ");
			// Retain all that is in $data after $identlength characters with replaced chr(10)'s and chr(13)'s and minus the first ':'
			$rawcommand			= substr($data, $identlength);
			$ex['fullcommand']	= substr(str_replace(array(chr(10), chr(13)), '', $rawcommand), 1);
			// Split the commandstring up into a second array with words
			$ex['command']		= explode(" ", $ex['fullcommand']);
			// The username!hostname of the sender (don't include the first ':' - start from 1)
			$ex['ident']		= substr($ex[0], 1);
			// Only the username of the sender (one step extra because only that before the ! wants to be parsed)
			$hostlength			= strlen(strstr($ex[0], '!'));
			$ex['username']		= substr($ex[0], 1, -$hostlength);
			// The receiver of the sent message (either the channelname or the bots nickname)
			$ex['receiver']		= (isset($ex[2]) ? $ex[2] : "");
			// Interpret the type of message received ("PRIVATE" or "CHANNEL") depending on the receiver
			$ex['type']			= $this->interpret_privmsg($ex['receiver']);
			// Get whether the user is authenticated
			$ex['authlevel'] 	= $this->is_authenticated($ex['ident']);
			
			return $ex;
		}
		
		/**
		 * Interprets the receiver of a PRIVMSG sent and returns whether it is one from a user (a private message) or one sent in the channel.
		 * 
		 * @access private
		 * @param string $privmsg The message to be interpretted
		 * @return void
		 */
		private function interpret_privmsg($privmsg)
		{
			// Regular expressions to match "#channel"
			$regex = '/(#)((?:[a-z][a-z]+))/is';
			
			// If the receiver includes a channelname
			if(preg_match($regex, $privmsg))
			{
				// ... it was sent to the channel
				$message = "CHANNEL";
				return $message;
			}
			// Or if the sent message's receiver is the bots nickname
			elseif($privmsg == BOT_NICKNAME)
			{
				// ... it is a private message
				$message = "PRIVATE";
				return $message;
			}
		}
		
		/**
		 * Checks if sender of message is in the list of authenticated users. (username!hostname)
		 * 
		 * @access private
		 * @param string $ident The username!hostname of the user we want to check
		 * @return boolean $authenticated TRUE for an authenticated user, FALSE for one which isn't
		 */
		private function is_authenticated($ident)
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