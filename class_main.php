<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *	
	 * TODO:
	 * 		- Documentation
	 *		- Structure the class, perhaps outsource some features of main() *PARTIALLY DONE*
	 *		- Make use of usleep() to minimize CPU load
	 *		- *DONE* Ability to reload speech array
	 *		- Ability to add speech on-the-fly
	 *		- Users and channel abilities
	 *			- *DONE* Ability to voice and de-voice (+v / -v)
	 *			- Ability to set channel topic
	 *			- Ability to /whois a user and get response in a PM
	 *			- Ability to invite a user
	 *		- Bot abilities
	 *			- *DONE* Ability to use /me
	 *			- Ability to use /notice
	 *			- *NOT WORKING* Ability to change nickname for the session
	 */

class IRCBot
{	
	// This is going to hold the data received from the socket
	var $data;

	// This is going to hold all of the messages from both server and client
	var $ex = array();
	
	// This is going to hold all of the authenticated users
	var $users = array();
	
	// This is going to hold our starttime
	var $starttime;
	
	// This is going to hold our responses
	var $response;

	/**
	 * Construct item, opens the server connection, logs the bot in, import stuff
	 */
	function __construct()
	{
		// Our TCP/IP connection
		global $socket;
		$socket = fsockopen(SERVER_IP, SERVER_PORT);
		
		// Include functions
		include("class_func.php");
		
		$this->login();
		
		// Open the users.inc file
		$file = fopen("users.inc", "r");
		$userlist = fread($file, filesize("users.inc"));
		fclose($file);
		// Split each line into separate entry in a global array
		global $users;
		$users = explode("\n", $userlist);
		
		// Include responses and unset the variable to save memory
		$this->response = reload_speech();
		
		// Start microtime
		$this->starttime = microtime_float();
		
		$this->main();
	}

	/**
	 * Logs the bot in on the server
	 */
	function login()
	{
		send_data('USER', BOT_NICKNAME.' douglasstridsberg.com '.BOT_NICKNAME.' :'.BOT_NAME);
		send_data('NICK', BOT_NICKNAME);
		debug_message("Bot connected successfully!");
	}

	/**
	 * This is the workhorse function, grabs the data from the server and displays on the browser if DEBUG_OUTPUT is true.
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
			$this->ex = explode(" ", $this->data);

			if($this->ex[0] == "PING")
			{
				// Plays ping-pong with the server to stay connected
				send_data("PONG", $this->ex[1]);
				debug_message("PONG was sent.");
			}
			
			// Get length of everything before command including last space
			$identlength = strlen($this->ex[0]." ".$this->ex[1]." ".$this->ex[2]." ");
			// Retain all that is in $data after $identlength characters with replaced chr(10)'s and chr(13)'s and minus the first ':'
			$rawcommand = substr($this->data, $identlength);
			$this->ex['fullcommand'] = substr(str_replace(array(chr(10), chr(13)), '', $rawcommand), 1);
			// Split the commandstring up into a second array with words
			$this->ex['command'] = explode(" ", $this->ex['fullcommand']);
			
			// The username!hostname of the sender (don't include the first ':' - start from 1)
			$this->ex['ident']		= substr($this->ex[0], 1);
			
			// Only the username of the sender (one step extra because only that before the ! wants to be parsed)
			$hostlength = strlen(strstr($this->ex[0], '!'));
			$this->ex['username']	= substr($this->ex[0], 1, -$hostlength);
			
			// The receiver of the sent message
			$this->ex['receiver']	= $this->ex[2];
			
			// Interpret the type of message received ("PRIVATE" or "CHANNEL") depending on the receiver
			$this->ex['type']		= interpret_privmsg($this->ex['receiver']);
			
			// If user is authenticated
			if(is_authenticated($this->ex['ident']) == 1)
			{
				// If the received message is a PM
				if($this->ex['type'] == "PRIVATE")
				{
					// List of commands the bot responds to from an authenticated user only via PM
					switch(strtolower($this->ex['command'][0]))
					{
						case '!join':
							// 0 is the command and 1 is the channel
							send_data("PRIVMSG", "Channel ".join_channel($this->ex['command'][1])." was joined!", $this->ex['username']);
						break;
						case '!part':
							// 0 is the command and 1 is the channel
							send_data("PRIVMSG", "Channel ".part_channel($this->ex['command'][1])." was parted!", $this->ex['username']);
						break;
						case '!quit':
							send_data("PRIVMSG", "Bot is quitting!", $this->ex['username']);
							debug_message("Bot is quitting!");
							send_data("QUIT", ":".BOT_QUITMSG);
						break;
						case '!info':
							send_data("PRIVMSG", "dG52's PHP IRC Bot", $this->ex['username']);
							debug_message("Info was sent to ".$this->ex['username']."!");
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
						case '!say':
							// Length of command plus channel
							$length = strlen($this->ex['command'][0]." ".$this->ex['command'][1]);
							send_data("PRIVMSG", substr($this->ex['fullcommand'], $length + 1), $this->ex['command'][1]);
						break;
						case '!op':
							// 0 is the command, 1 is the channel and 2 is the user
							op_user($this->ex['command'][1], $this->ex['command'][2], true);
							send_data("PRIVMSG", "User ".($this->ex['command'][2] ? $this->ex['command'][2] : $this->ex['username'])." was given operator status!", $this->ex['username']);
						break;
						case '!deop':
							// 0 is the command, 1 is the channel and 2 is the user
							op_user($this->ex['command'][1], $this->ex['command'][2], false);
							send_data("PRIVMSG", "User ".($this->ex['command'][2] ? $this->ex['command'][2] : $this->ex['username'])." was taken operator status!", $this->ex['username']);
						break;
						case '!voice':
							// 0 is the command, 1 is the channel and 2 is the user
							voice_user($this->ex['command'][1], $this->ex['command'][2], true);
							send_data("PRIVMSG", "User ".($this->ex['command'][2] ? $this->ex['command'][2] : $this->ex['username'])." was given voice!", $this->ex['username']);
						break;
						case '!devoice':
							// 0 is the command, 1 is the channel and 2 is the user
							voice_user($this->ex['command'][1], $this->ex['command'][2], false);
							send_data("PRIVMSG", "User ".($this->ex['command'][2] ? $this->ex['command'][2] : $this->ex['username'])." was de-voiced!", $this->ex['username']);
						break;
					}
				}
				elseif($this->ex['type'] == "CHANNEL")
				{
					// List of commands the bot responds to from an authenticated user via channel
					switch(strtolower($this->ex['command'][0]))
					{
						case '!say':
							// Subtract 4 characters (!say) plus a space
							send_data("PRIVMSG", substr($this->ex['fullcommand'], 5), $this->ex['receiver']);
						break;
						case '!me':
							// Subtract 3 characters (!me) plus a space
							send_data("PRIVMSG", "/me ".substr($this->ex['fullcommand'], 4), $this->ex['receiver']);
						break;
						case '!info':
							send_data("PRIVMSG", "dG52's PHP IRC Bot", $this->ex['username']);
							debug_message("Info was sent to ".$this->ex['username']."!");
						break;
					}
				}
			}
			
			// List of commands the bot responds to from any user via channel
			// If the bots nickname is found in the full command sent to a channel
			if($this->ex['type'] == "CHANNEL")
			{
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
					case '!help':
						$keyword = strtolower($this->ex['command'][1]);
						if($this->response['info'][$keyword])
						{
							// If there is a keyword available
							send_data("PRIVMSG", $this->response['info'][$keyword], $this->ex['receiver']);
							debug_message("Keyword \"".$this->ex['command'][1]."\ was defined upon request by ".$this->ex['username']."!");
						}
						else
						{
							// If there is no keyword available
							send_data("PRIVMSG", "No help for this item was found", $this->ex['receiver']);
							debug_message("Keyword \"".$this->ex['command'][1]."\ was undefined but requested by ".$this->ex['username']."!");
						}
					break;
					case '!google':
						$query = "q=".urlencode(substr($this->ex['fullcommand'], 8));
						send_data("PRIVMSG", "http://www.google.com/search?".$query, $this->ex['receiver']);
					break;
					case '!youtube':
						$query = "search_query=test".urlencode(substr($this->ex['fullcommand'],9));
						send_data("PRIVMSG", "http://www.youtube.com/results?".$query, $this->ex['receiver']);
					break;
				}
			}
		}
	}
}

?>