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
			print_header();
			
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
							if(strtolower(substr($this->parsedData['command'][0], 1)) == "quit")
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
	}
?>