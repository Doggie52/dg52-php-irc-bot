<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Main class holding the main bot class and includes to other functions
	 */
	
	/**
	 * IRCBot class.
	 */
	class IRCBot
	{
		// This is going to hold the data received from the socket
		var $rawData;
		
		// This is going to hold all of the messages from both server and client
		var $parsedData = array();
		
		// This is going to hold our responses
		var $response;
		
		// This is going to hold our starting time
		var $startTime;
		
		// This holds the plugins object
		var $plugins;
		
		/**
		 * Constructs the bot by opening the server connection and CLI interface, logging the bot in and importing list of administrators.
		 *
		 * @access public
		 * @return void
		 */
		function __construct()
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
			
			// Instantiate plugin object and trigger the load
			$this->plugins = new Plugin;
			$this->plugins->triggerEvent(array("load"));
			

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
			print_header();
			
			// Log bot in
			$channels = explode(" ", BOT_CHANNELS);
			$this->login($channels);
			
			// Stores list of administrators
			global $users;
			$users = reload_users();
			
			// Include responses
			$this->response = reload_speech();
			
			// Declare the starttime
			$this->starttime = time();
			
			// Initializes the main bot workhorse
			$this->main();
			
			// Closes the socket
			fclose($socket) or die('Unable to close the socket!');
		}
		
		/**
		 * Registers the bot on the server and joins specified channels.
		 *
		 * @access public
		 * @param array $channels An array of channels to join directly on connect
		 * @return void
		 */
		function login($channels)
		{
			send_data('USER', BOT_NICKNAME.' douglasstridsberg.com '.BOT_NICKNAME.' :'.BOT_NAME);
			send_data('NICK', BOT_NICKNAME);
			debug_message("Bot connected successfully!");
			// Temporarily tap into the socket
			global $socket;
			while(!feof($socket))
			{
				// If "MOTD" is found the bot has been fully connected. Break the loop
				if(fgets($socket) && strpos(fgets($socket), "MOTD"))
				{
					debug_message("Bot was greeted.");
					break;
				}
			}
			foreach($channels as $channel)
			{
				join_channel($channel);
			}
		}
		
		/**
		 * This is the workhorse function, grabs the data from the server and displays on the browser if DEBUG_OUTPUT is true.
		 *
		 * @access public
		 * @return void
		 */
		function main()
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
					
					$this->parsedData = parse_raw_command($this->rawData);
					
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
					if(strtolower($this->parsedData['command'][0][0]) == "!")
					{
						// Distinguish between channelmsg and privmsg - the latter needs 'username' as the $from parameter
						if($this->parsedData['type'] == "CHANNEL")
						{
							$this->plugins->triggerEvent("command",
								substr(strtolower($this->parsedData['command'][0]), 1),
								$this->parsedData['type'],
								$this->parsedData['receiver'],
								$this->parsedData['authlevel']);
						}
						elseif($this->parsedData['type'] == "PRIVATE")
						{
							$this->plugins->triggerEvent("command",
								substr(strtolower($this->parsedData['command'][0]), 1),
								$this->parsedData['type'],
								$this->parsedData['username'],
								$this->parsedData['authlevel']);
						}
					}
					// If the message is a message - to be implemented
					else
					{
						
					}
				}
			}
		}
	}
?>