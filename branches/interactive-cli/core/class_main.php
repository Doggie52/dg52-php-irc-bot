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
		var $data;
		
		// This is going to hold all of the messages from both server and client
		var $ex = array();
		
		// This is going to hold all of the authenticated users
		var $users = array();
		
		// This is going to hold our responses
		var $response;
		
		// This is going to hold our starting time
		var $starttime;
		
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
			
			// Open the users.inc file
			$file = fopen(USERS_PATH, "r");
			// Turn the hostnames into lowercase (does not compromise security, as hostnames are unique anyway)
			$userlist = strtolower(fread($file, filesize(USERS_PATH)));
			fclose($file);
			// Split each line into separate entry in a global array
			global $users;
			$users = explode("\n", $userlist);
			
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
				if($this->data = fgets($socket))
				{				
					// If the debug output is turned on, spew out all data received from server
					if(DEBUG_OUTPUT)
					{
						echo nl2br($this->data);
					}
					flush();
					
					$this->ex = parse_raw_command($this->data);
					
					if($this->ex[0] == "PING")
					{
						// Plays ping-pong with the server to stay connected
						send_data("PONG", $this->ex[1]);
						if(!SUPPRESS_PING)
						{
							debug_message("PONG was sent.");
						}
					}
					
					/**
					 * List of commands
					 *
					 * @structure
					 * 		- Authenticated users
					 * 			- PM only
					 * 			- Channel only
					 * 			- Both
					 * 		- Non-authenticated users
					 * 			- PM only
					 * 			- Channel only
					 * 			- Both
					 * 		- Both auth and non-auth users
					 * 			- PM only
					 * 			- Channel only
					 * 			- Both
					 */
					
					// Is the user authenticated?
					$authlevel = is_authenticated($this->ex['ident']);
					
					/**
					 * [AUTH] Commands for authenticated users
					 */
					if(@$this->ex['command'][0][0] == "!" && $authlevel == 1)
					{
						/**
						 * [AUTH] PM only
						 */
						if($this->ex['type'] == "PRIVATE")
						{
							switch(strtolower($this->ex['command'][0]))
							{
								case '!h':
								case '!help':
									// Lock up help for both authenticated users and non-authenticated users
									// Put them in individual strings to ensure both are run
									$authed = lookup_help((isset($this->ex['command'][1]) ? $this->ex['command'][1] : ""), $this->response['commands'][1], $this->ex['username']);
									$nonauthed = lookup_help((isset($this->ex['command'][1]) ? $this->ex['command'][1] : ""), $this->response['commands'][0], $this->ex['username']);
									// If both return 0 the command was not defined
									if($authed == '0'	&& $nonauthed == '0')
									{
										send_data("PRIVMSG", "Command \"".$this->ex['command'][1]."\" was not defined in the documentation!", $this->ex['username']);
									}
									// However if one of them returns 1 the command was defined (or all commands were listed)
									elseif($authed == '1' || $nonauthed == '1')
									{
										send_data("PRIVMSG", "Type !help <command> to see the corresponding syntax.", $this->ex['username']);
									}
									break;
								case '!j':
								case '!join':
									// 0 is the command and 1 is the channel
									send_data("PRIVMSG", "Channel ".join_channel($this->ex['command'][1])." was joined!", $this->ex['username']);
									break;
								case '!p':
								case '!part':
									// 0 is the command and 1 is the channel
									send_data("PRIVMSG", "Channel ".part_channel($this->ex['command'][1])." was parted!", $this->ex['username']);
									break;
								case '!q':
								case '!quit':
									send_data("PRIVMSG", "Bot is quitting!", $this->ex['username']);
									send_data("QUIT", ":".BOT_QUITMSG);
									debug_message("Bot has disconnected and been turned off!");
									break;
								case '!reload':
									reload_speech();
									send_data("PRIVMSG", "Speech was reloaded!", $this->ex['username']);
									break;
								case '!s':
								case '!say':
									// Length of command plus channel
									$length = strlen($this->ex['command'][0]." ".$this->ex['command'][1]);
									send_data("PRIVMSG", substr($this->ex['fullcommand'], $length+1), to_channel($this->ex['command'][1]));
									break;
								case '!op':
									mode_user($this->ex, "+o");
									send_data("PRIVMSG", "User ".(isset($this->ex['command'][2]) ? $this->ex['command'][2] : $this->ex['username'])." was given operator status!", $this->ex['username']);
									break;
								case '!deop':
									mode_user($this->ex, "-o");
									send_data("PRIVMSG", "User ".(isset($this->ex['command'][2]) ? $this->ex['command'][2] : $this->ex['username'])." was taken operator status!", $this->ex['username']);
									break;
								case '!voice':
									mode_user($this->ex, "+v");
									send_data("PRIVMSG", "User ".(isset($this->ex['command'][2]) ? $this->ex['command'][2] : $this->ex['username'])." was given voice!", $this->ex['username']);
									break;
								case '!devoice':
									mode_user($this->ex, "-v");
									send_data("PRIVMSG", "User ".(isset($this->ex['command'][2]) ? $this->ex['command'][2] : $this->ex['username'])." was de-voiced!", $this->ex['username']);
									break;
							}
						}
						/**
						 * [AUTH] Channel only
						 */
						if($this->ex['type'] == "CHANNEL")
						{
							switch(strtolower($this->ex['command'][0]))
							{
								case '!me':
									// Subtract 3 characters (!me) plus a space
									send_data("PRIVMSG", "/me ".substr($this->ex['fullcommand'], 4), $this->ex['receiver']);
									break;
							}
						}
						/**
						 * [AUTH] Both PM and channel
						 */
						switch(strtolower($this->ex['command'][0]))
						{
							case '!info':
								print_info($this->ex['username'], $this->starttime);
								break;
							case '!add':
								$line = substr($this->ex['fullcommand'], 5);
								// Writes the line to the file
								if(write_definition($line, $this->response['info']) != 0)
								{
									debug_message("Keyword \"".$this->ex['command'][1]."\" was defined by ".$this->ex['username']."!");
									send_data("PRIVMSG", "A definition for keyword \"".$this->ex['command'][1]."\" was added!", $this->ex['username']);
									// Reload responses
									$this->response = reload_speech();
								}
								else
								{
									send_data("PRIVMSG", "An error occurred when adding the definition!", $this->ex['username']);
								}
								break;
							case '!t':
							case '!topic':
								$length = strlen($this->ex['command'][0]);
								// If the receiver message is a PM, assume that the channelname is included
								if($this->ex['type'] == "PRIVATE")
								{
									$channel = $this->ex['command'][1];
									$topic = substr($this->ex['fullcommand'], 1+$length+1+strlen($channel));
								}
								else
								{
									$channel = $this->ex['receiver'];
									$topic = substr($this->ex['fullcommand'], $length+1);
								}
								set_topic($channel, $topic);
								send_data("PRIVMSG", "Topic of ".$channel." was changed!", $this->ex['username']);
								break;
							case '!i':
							case '!invite':
									// If the receiver message is a PM, assume that the channelname is included
									if($this->ex['type'] == "PRIVATE")
									{
										// If both a valid username and a channel has been entered
										if(isset($this->ex['command'][1]) && isset($this->ex['command'][2]))
										{
											$username = $this->ex['command'][1];
											$channel = $this->ex['command'][2];
											send_data("INVITE", $username." ".to_channel($channel));
											send_data("PRIVMSG", "User ".$username." was invited to ".to_channel($channel)."!", $this->ex['username']);
										}
										else
										{
											send_data("PRIVMSG", "Not enough data was entered!", $this->ex['username']);
										}
									}
									else
									{
										// If a valid username has been entered
										if(isset($this->ex['command'][1]))
										{
											$username = $this->ex['command'][1];
											$channel = $this->ex['receiver'];
											send_data("INVITE", $username." ".to_channel($channel));
											send_data("PRIVMSG", "User ".$username." was invited to ".to_channel($channel)."!", $this->ex['username']);
										}
										else
										{
											send_data("PRIVMSG", "Not enough data was entered!", $this->ex['username']);
										}
									}
								break;
						}
					}
					
					/**
					 * [non-AUTH] Commands for non-authenticated users
					 */
					if(@$this->ex['command'][0][0] == "!" && $authlevel == 0)
					{
						/**
						 * [non-AUTH] PM only
						 */
						if($this->ex['type'] == "PRIVATE")
						{
							switch(strtolower($this->ex['command'][0]))
							{
								case '!h':
								case '!help':
									// Only look up help for non-authenticated users
									lookup_help((isset($this->ex['command'][1]) ? $this->ex['command'][1] : ""), $this->response['commands'][0], $this->ex['username']);
									break;
								default:
									send_data("PRIVMSG", "Either the command was not found or you lack the privileges to use it!", $this->ex['username']);
									break;
							}
						}
						/**
						 * [non-AUTH] Channel only
						 */
						if($this->ex['type'] == "CHANNEL")
						{
						}
						/**
						 * [non-AUTH] Both PM and channel
						 */
					}
					/**
					 * [both] Commands for both authenticated and non-authenticated users
					 */
					if(@$this->ex['command'][0][0] == "!")
					{
						/**
						 * [both] PM only
						 */
						if($this->ex['type'] == "PRIVATE")
						{
						}
						/**
						 * [both] Channel only
						 */
						if($this->ex['type'] == "CHANNEL")
						{
							// If the bots nickname is found in the full command sent to a channel
							if(stristr($this->ex['fullcommand'], BOT_NICKNAME) != FALSE)
							{
								// Seed the random number generator and shuffle the array, then bring out a random key and say it in the channel
								srand((float)microtime() * 10000);
								shuffle($this->response['mention']);
								$randommention = array_rand($this->response['mention'], 1);
								// Match %username% to the user who mentioned the bot
								$response = preg_replace("/%username%/", $this->ex['username'], $this->response['mention'][$randommention]);
								send_data("PRIVMSG", $response, $this->ex['receiver']);
								debug_message("Bot was mentioned and thus it replied!");
							}
							switch(strtolower($this->ex['command'][0]))
							{
								case '!d':
								case '!define':
									$keyword = strtolower($this->ex['command'][1]);
									if($keyword == "list")
									{
										// If the user wants to list all definitions
										send_data("PRIVMSG", "All definitions available:", $this->ex['username']);
										$keywordlist = "";
										foreach($this->response['info'] as $term => $definition)
										{
											$keywordlist .= $term.", ";
										}
										send_data("PRIVMSG", substr($keywordlist, 0, -2), $this->ex['username']);
									}
									elseif(isset($this->response['info'][$keyword]))
									{
										// If the entered keyword matches a definition available
										send_data("PRIVMSG", $this->response['info'][$keyword], $this->ex['receiver']);
										debug_message("Keyword \"".$this->ex['command'][1]."\" was defined upon request by ".$this->ex['username']."!");
									}
									else
									{
										// If the entered keyword does not match a definition available
										send_data("PRIVMSG", "No help for this item was found", $this->ex['receiver']);
										debug_message("Keyword \"".$this->ex['command'][1]."\" was undefined but requested by ".$this->ex['username']."!");
									}
									break;
								case '!thetime':
									$date = date("H:ia T");
									send_data("PRIVMSG", $date, $this->ex['receiver']);
									break;
								case '!google':
									$query = substr($this->ex['fullcommand'], 8);
									$results = google_search_html($query);
									foreach($results as $results)
									{
										send_data("PRIVMSG", "#".$results['id']." ".format_text("bold", $results['title'])." (".$results['url'].")", $this->ex['receiver']);
										send_data("PRIVMSG", format_text("italic", $results['description']), $this->ex['receiver']);
									}
									break;
								case '!youtube':
									$query = substr($this->ex['fullcommand'], 9);
									$results = youtube_search_html($query);
									foreach($results as $results)
									{
										send_data("PRIVMSG", "#".$results['id']." ".format_text("bold", $results['title'])." (".$results['url'].")", $this->ex['receiver']);
										send_data("PRIVMSG", format_text("italic", $results['description']), $this->ex['receiver']);
									}
									break;
							}
						}
					}
				}
			}
		}
	}
?>