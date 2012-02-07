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
		
		// This is going to hold all the current data object from the server
		private $data;
		
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
			include("core/class_data.php");
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
			
			pluginHandler::load_plugins();
			// Trigger plugin load
			pluginHandler::trigger_hook('load');
			// When the loop is broken, we have been greeted
			pluginHandler::trigger_hook('connect');
			
			// Stores list of administrators
			global $users;
			$users = $this->reload_users();
			
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
						echo $this->rawData;
					}
					flush();
					
					$this->data = new Data($this->rawData);

					if($this->data->type == Data::PING)
					{
						// Explode raw data to get server
						$_temp_expl = explode(" ", $this->rawData);
						// Plays ping-pong with the server to stay connected
						send_data("PONG", $_temp_expl[1]);
						if(!SUPPRESS_PING)
						{
							debug_message("PONG was sent.");
						}
					}
					
					// If the message is a command
					if($this->data->type == Data::COMMAND)
					{
						PluginHandler::run_command($this->data->command, $this->data);
					}
					else
					{
						if($this->data->origin == Data::CHANNEL)
						{
							PluginHandler::trigger_hook('channel_message', $this->data);
						}
						elseif($this->data->origin == Data::PRIVMSG)
						{
							PluginHandler::trigger_hook('private_message', $this->data);
						}
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
		private function print_header()
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
		 * (re)Loads the array of administrators
		 *
		 * @access private
		 * @return array $users The array of administrators
		 */
		private function reload_users()
		{
			// Open the users.inc file
			$file = fopen(USERS_PATH, "r");
			// Turn the hostnames into lowercase (does not compromise security, as hostnames are unique anyway)
			$userlist = strtolower(@fread($file, filesize(USERS_PATH)));
			fclose($file);
			if($userlist)
			{
				// Split each line into separate entry in the returned array
				$users = explode("\n", $userlist);
				debug_message("The list of administrators was successfully loaded into the system!");
				return $users;
			}
			else
			{
				debug_message("Something went wrong when loading the list of administrators.");
				return false;
			}
		}
	
	}
?>