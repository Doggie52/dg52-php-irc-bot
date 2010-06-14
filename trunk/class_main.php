<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *	
	 * TODO:
	 * 		- *PARTIALLY DONE* Documentation for bot-commands
	 *		- Documentation for class
	 *		- *PARTIALLY DONE* Structure the class, perhaps outsource some features of main()
	 *		- Make use of usleep() to minimize CPU load
	 *		- *DONE* Ability to reload speech array
	 *		- *DONE* Ability to add speech on-the-fly
	 *		- *DONE* Ability to format sent PMs
	 *		- Users and channel abilities
	 *			- *DONE* Ability to voice and de-voice (+v / -v)
	 *			- *DONE* Ability to set channel topic
	 *			- Ability to /whois a user and get response in a PM
	 *			- *DONE* Ability to invite a user
	 *			- Ability to add bot admin on-the-fly
	 *		- Bot abilities
	 *			- *DONE* Ability to use /me
	 *			- Ability to use /notice
	 *			- *NOT WORKING* Ability to change nickname for the session
	 */

/**
 * IRCBot class.
 * 
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
	 * Construct item, opens the server connection, logs the bot in, import stuff
	 *
	 * @access public
	 * @return void
	 */
	function __construct()
	{		
		// Our TCP/IP connection
		global $socket;
		$socket = fsockopen(SERVER_IP, SERVER_PORT);
		
		// Include functions
		include("class_func.php");
		
		// Print header
		print_header();
		
		// Log bot in
		$this->login();
		
		// Open the users.inc file
		$file = fopen("users.inc", "r");
		// Turn the hostnames into lowercase (does not compromise security, as hostnames are unique anyway)
		$userlist = strtolower(fread($file, filesize("users.inc")));
		fclose($file);
		// Split each line into separate entry in a global array
		global $users;
		$users = explode("\n", $userlist);
		
		// Include responses
		$this->response = reload_speech();
		
		// Declare the starttime
		$this->starttime = time();
		
		$this->main();
	}

	/**
	 * Logs the bot in on the server
	 *
	 * @access public
	 * @return void
	 */
	function login()
	{
		send_data('USER', BOT_NICKNAME.' douglasstridsberg.com '.BOT_NICKNAME.' :'.BOT_NAME);
		send_data('NICK', BOT_NICKNAME);
		debug_message("Bot connected successfully!");
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
		while(!feof($socket))
		{
			$this->data = fgets($socket);
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
				debug_message("PONG was sent.");
			}
			
			// If user is authenticated
			if(is_authenticated($this->ex['ident']) == 1)
			{
				// List of commands the bot responds to from an authenticated user either via PM or channel
				switch(strtolower($this->ex['command'][0]))
				{
					case '!info':
						print_info($this->ex['username'], $this->starttime);
						break;
					case '!add':
						$line = substr($this->ex['fullcommand'], 5);
						// Writes the line to the file
						write_response($line);
						debug_message("Keyword \"".$this->ex['command'][1]."\" was defined by ".$this->ex['username']."!");
						send_data("PRIVMSG", "Keyword \"".$this->ex['command'][1]."\" was added!", $this->ex['username']);
						// Reload responses
						$this->response = reload_speech();
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
							$channel = $this->ex['command'][2];
							$username = $this->ex['command'][1];
						}
						else
						{
							$channel = $this->ex['receiver'];
							$username = $this->ex['command'][1];
						}
						send_data("INVITE", $username." ".to_channel($channel));
						send_data("PRIVMSG", "User ".$username." was invited to ".to_channel($channel)."!", $this->ex['username']);
						break;
				}

				if($this->ex['type'] == "PRIVATE")
				{
					// List of commands the bot responds to from an authenticated user only via PM
					switch(strtolower($this->ex['command'][0]))
					{
						case '!h':
						case '!help':
							lookup_help((isset($this->ex['command'][1]) ? $this->ex['command'][1] : ""), $this->response['commands']['pm'], $this->ex['username']);
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
							debug_message("Bot has quitted!\r\n");
							break;
						case '!reload':
							reload_speech();
							send_data("PRIVMSG", "Speech was reloaded!", $this->ex['username']);
							break;
						/*
						case '!nick':
							// interpret_privmsg will not work with PM's until BOT_NICKNAME can be changed
							send_data("NICK", $this->ex['command'][1]);
							send_data("PRIVMSG", "My nickname was changed to ".$this->ex['command'][1]."!", );
							debug_message("BOT_NICKNAME was re-defined as ".$this->ex['command'][1]."!", $this->ex['username']);
							break;
						*/
						case '!s':
						case '!say':
							// Length of command plus channel
							$length = strlen($this->ex['command'][0]." ".$this->ex['command'][1]);
							send_data("PRIVMSG", substr($this->ex['fullcommand'], $length+1), to_channel($this->ex['command'][1]));
							break;
						case '!op':
							// 0 is the command, 1 is the channel and 2 is the user
							op_user($this->ex, true);
							send_data("PRIVMSG", "User ".(isset($this->ex['command'][2]) ? $this->ex['command'][2] : $this->ex['username'])." was given operator status!", $this->ex['username']);
							break;
						case '!deop':
							// 0 is the command, 1 is the channel and 2 is the user
							op_user($this->ex, false);
							send_data("PRIVMSG", "User ".(isset($this->ex['command'][2]) ? $this->ex['command'][2] : $this->ex['username'])." was taken operator status!", $this->ex['username']);
							break;
						case '!voice':
							// 0 is the command, 1 is the channel and 2 is the user
							voice_user($this->ex, true);
							send_data("PRIVMSG", "User ".(isset($this->ex['command'][2]) ? $this->ex['command'][2] : $this->ex['username'])." was given voice!", $this->ex['username']);
							break;
						case '!devoice':
							// 0 is the command, 1 is the channel and 2 is the user
							voice_user($this->ex, false);
							send_data("PRIVMSG", "User ".(isset($this->ex['command'][2]) ? $this->ex['command'][2] : $this->ex['username'])." was de-voiced!", $this->ex['username']);
							break;
					}
				}
				elseif($this->ex['type'] == "CHANNEL")
				{
					// List of commands the bot responds to from an authenticated user via channel
					switch(strtolower($this->ex['command'][0]))
					{
						case '!s':
						case '!say':
							// Subtract the amount of characters of the command plus a space
							$length = strlen($this->ex['command'][0]);
							send_data("PRIVMSG", substr($this->ex['fullcommand'], $length+1), $this->ex['receiver']);
							break;
						case '!me':
							// Subtract 3 characters (!me) plus a space
							send_data("PRIVMSG", "/me ".substr($this->ex['fullcommand'], 4), $this->ex['receiver']);
							break;
					}
				}
			}
			
			// List of commands the bot responds to from any user via channel
			if($this->ex['type'] == "CHANNEL")
			{
			     // If the bots nickname is found in the full command sent to a channel
				if(stristr($this->ex['fullcommand'], BOT_NICKNAME) != FALSE)
				{
					// Shuffle the array, bring out a random key and say it in the channel
					shuffle($this->response['mention']);
					$randommention = array_rand($this->response['mention'], 1);
					send_data("PRIVMSG", $this->response['mention'][$randommention], $this->ex['receiver']);
					debug_message("Bot was mentioned and thus it replied!");
				}
				switch(strtolower($this->ex['command'][0]))
				{
					case '!define':
						$keyword = strtolower($this->ex['command'][1]);
						if($this->response['info'][$keyword])
						{
							// If there is a keyword available
							send_data("PRIVMSG", $this->response['info'][$keyword], $this->ex['receiver']);
							debug_message("Keyword \"".$this->ex['command'][1]."\" was defined upon request by ".$this->ex['username']."!");
						}
						else
						{
							// If there is no keyword available
							send_data("PRIVMSG", "No help for this item was found", $this->ex['receiver']);
							debug_message("Keyword \"".$this->ex['command'][1]."\" was undefined but requested by ".$this->ex['username']."!");
						}
						break;
					case '!google':
						$query = "q=".urlencode(substr($this->ex['fullcommand'], 8));
						send_data("PRIVMSG", "http://www.google.com/search?".$query, $this->ex['receiver']);
						break;
					case '!youtube':
						$query = "search_query=".urlencode(substr($this->ex['fullcommand'],9));
						send_data("PRIVMSG", "http://www.youtube.com/results?".$query, $this->ex['receiver']);
						break;
				}
			}
		}
	}
}

?>