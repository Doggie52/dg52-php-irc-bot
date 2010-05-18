<?php
	/*
		dG52 PHP IRC Bot

		Author: Douglas Stridsberg
			Email: doggie52@gmail.com
			URL: www.douglasstridsberg.com
		
		TODO:
			- Structure the class, perhaps outsource some features of main()
			- Make use of usleep() to minimize CPU load
			- Users and channel abilities
				- Ability to voice and de-voice (+v / -v)
				- Ability to set channel topic
				- Ability to /whois a user and get response in a PM
				- Ability to invite a user
			- Bot abilities
				- Ability to use /me
				- Ability to use /notice
	*/

class IRCBot
{
	// This is going to hold our TCP/IP connection
	var $socket;
	
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
	 Construct item, opens the server connection, logs the bot in, import stuff
	*/
	function __construct()
	{
		$this->socket = fsockopen(SERVER_IP, SERVER_PORT);
		$this->login();
		
		// Open the users.inc file
		$file = fopen("users.inc", "r");
		$userlist = fread($file, filesize("users.inc"));
		fclose($file);
		// Split each line into separate entry in array
		$this->users = explode("\n", $userlist);
		
		// Include responses
		include("speech.php");
		$this->response = $response;
		
		// Start microtime
		$this->starttime = $this->microtime_float();
		
		$this->main();
	}

	/**
	 Logs the bot in on the server
	*/
	function login()
	{
		$this->send_data('USER', BOT_NICKNAME.' douglasstridsberg.com '.BOT_NICKNAME.' :'.BOT_NAME);
		$this->send_data('NICK', BOT_NICKNAME);
		$this->debug_message("Bot connected successfully!");
	}
	
	/**
	 Checks current microtime
	 http://www.developertutorials.com/blog/php/php-measure-max-execution-time-script-execution-time-83/
	*/
	function microtime_float()
	{
		list($utime, $time) = explode(" ", microtime());
		return ((float)$utime + (float)$time);
	}

	/**
	 This is the workhorse function, grabs the data from the server and displays on the browser
	*/
	function main()
	{
		while(!feof($this->socket))
		{
			$this->data = fgets($this->socket);
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
				$this->send_data("PONG", $this->ex[1]);
				$this->debug_message("PONG was sent.");
			}
			
			// Parses the raw command
			$this->parse_raw_command();			
			
			// If user is authenticated
			if($this->is_authenticated($this->ex['ident']) == 1)
			{
				if($this->ex['type'] == "PRIVATE")
				{
					// List of commands the bot responds to from an authenticated user only via PM
					switch(strtolower($this->ex['command'][0]))
					{
						case '!join':
							// 0 is the command and 1 is the channel
							$this->join_channel($this->ex['command'][1]);
							$this->debug_message("Channel ".$this->ex['command'][1]." was joined!");
							$this->send_data("PRIVMSG", "Channel ".$this->ex['command'][1]." was joined!", $this->ex['username']);
						break;
						case '!part':
							// 0 is the command and 1 is the channel
							$this->part_channel($this->ex['command'][1]);
							$this->debug_message("Channel ".$this->ex['command'][1]." was parted!");
							$this->send_data("PRIVMSG", "Channel ".$this->ex['command'][1]." was parted!", $this->ex['username']);
						break;
						case '!quit':
							$this->send_data("PRIVMSG", "Bot is quitting!", $this->ex['username']);
							$this->debug_message("Bot is quitting!");
							$this->send_data("QUIT", ":".BOT_QUITMSG);
						break;
						case '!info':
							$this->send_data("PRIVMSG", "dG52's PHP IRC Bot", $this->ex['username']);
							$this->debug_message("Info was sent to ".$this->ex['username']."!");
						break;
						case '!say':
							// Length of command plus channel
							$length = strlen($this->ex['command'][0]." ".$this->ex['command'][1]);
							$this->send_data("PRIVMSG", substr($this->ex['fullcommand'], $length + 1), $this->ex['command'][1]);
							$this->debug_message("\"".substr($this->ex['fullcommand'], $length + 1)."\" was sent to ".$this->ex['command'][1]."!");
						break;
						case '!op':
							// 0 is the command, 1 is the channel and 2 is the user
							$this->op_user($this->ex['command'][1], $this->ex['command'][2], true);
							$this->send_data("PRIVMSG", "User ".($this->ex['command'][2] ? $this->ex['command'][2] : $this->ex['username'])." was given operator status!", $this->ex['username']);
							$this->debug_message("User ".($this->ex['command'][2] ? $this->ex['command'][2] : $this->ex['username'])." was given operator status!");
						break;
						case '!deop':
							// 0 is the command, 1 is the channel and 2 is the user
							$this->op_user($this->ex['command'][1], $this->ex['command'][2], false);
							$this->send_data("PRIVMSG", "User ".($this->ex['command'][2] ? $this->ex['command'][2] : $this->ex['username'])." was taken operator status!", $this->ex['username']);
							$this->debug_message("User ".($this->ex['command'][2] ? $this->ex['command'][2] : $this->ex['username'])." was taken operator status!");
						break;
					}
				}
				elseif($this->ex['type'] == "CHANNEL")
				{
					// List of commands the bot responds to from an authenticated user via both PM and channel
					switch(strtolower($this->ex['command'][0]))
					{
						case '!say':
							// Subtract 4 characters (!say) plus a space
							$this->send_data("PRIVMSG", substr($this->ex['fullcommand'], 5), $this->ex['receiver']);
							$this->debug_message("\"".substr($this->ex['fullcommand'], $length + 1)."\" was sent to ".$this->ex['receiver']."!");
						break;
						case '!info':
							$this->send_data("PRIVMSG", "dG52's PHP IRC Bot", $this->ex['username']);
							$this->debug_message("Info was sent to ".$this->ex['username']."!");
						break;
					}
				}
			}
			
			// List of commands the bot responds to from any user
			// If the bots nickname is found in the full command sent to a channel
			if($this->ex['type'] == "CHANNEL")
			{
				if(stristr($this->ex['fullcommand'], BOT_NICKNAME) != FALSE)
				{
					// Shuffle the array, bring out a random key and say it in the channel
					shuffle($this->response['mention']);
					$randommention = array_rand($this->response['mention'], 1);
					$this->send_data("PRIVMSG", $this->response['mention'][$randommention], $this->ex['receiver']);
					$this->debug_message("Bot was mentioned and thus it replied!");
				}
				switch(strtolower($this->ex['command'][0]))
				{
					case '!help':
						$keyword = strtolower($this->ex['command'][1]);
						if($this->response['info'][$keyword])
						{
							// If there is a keyword available
							$this->send_data("PRIVMSG", $this->response['info'][$keyword], $this->ex['receiver']);
							$this->debug_message("Keyword \"".$this->ex['command'][1]."\ was defined upon request by ".$this->ex['username']."!");
						}
						else
						{
							// If there is no keyword available
							$this->send_data("PRIVMSG", "No help for this item was found", $this->ex['receiver']);
							$this->debug_message("Keyword \"".$this->ex['command'][1]."\ was undefined but requested by ".$this->ex['username']."!");
						}
					break;
				}
						
			}
			
			// Sleep for two seconds, giving other programs a cycle of their own
			// usleep(2000);
		}
	}

	/**
	 Sends data to the server. Important that basic structure of sent message is kept the same, otherwise it will fail.
	 
	 @param string $cmd The command you wish to send
	 @param string $msg The parameters you wish to pass to the command
	 @param string $rcvr The receiver of the message
	*/
	function send_data($cmd, $msg = null, $rcvr = null)
	{
		if($msg == null)
		{
			fputs($this->socket, $cmd."\r\n");
			$this->debug_message("Command ($cmd) was sent to the server.");
		}
		elseif($cmd == "PRIVMSG")
		{
			fputs($this->socket, $cmd." ".$rcvr." :".$msg."\r\n");
			$this->debug_message("Command ($cmd) with receiver ($rcvr) and message ($msg) was sent to the server.");
		}
		else
		{
			fputs($this->socket, $cmd." ".$msg."\r\n");
			$this->debug_message("Command ($cmd) with message ($msg) was sent to the server.");
		}
	}
	
	/**
	 Parses the raw commands sent by the server and splits them up into different parts, storing them in the $ex variable for future use.
	*/
	function parse_raw_command()
	{
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
		$this->ex['type']		= $this->interpret_privmsg($this->ex['receiver']);
	}
	
	/**
	 Interprets the receiver of a PRIVMSG sent and returns whether it is one from a user (a private message) or one sent in the channel.
	 
	 @param string $privmsg The message to be interpretted	 
	 @return string $message The channel and/or the username of the sender
	*/
	function interpret_privmsg($privmsg)
	{
		// Regular expressions to match "#channel"
		$re = '/(#)((?:[a-z][a-z]+))/is';
		
		// If the receiver includes a channelname
		if(preg_match($re, $privmsg))
		{
			// ... it was sent to the channel
			$message = "CHANNEL";
			$this->debug_message("The received message was of type [".$message."]!");
			return $message;
		}
		// Or if the sent message's receiver is the bots nickname
		elseif($privmsg == BOT_NICKNAME)
		{
			// ... it is a private message
			$message = "PRIVATE";
			$this->debug_message("The received message was of type [".$message."]!");
			return $message;
		}
	}
	
	/**
	 Checks if sender of message is in the list of authenticated users. (username!hostname)
	 
	 @param string $ident The username!hostname of the user we want to check
	 @param boolean true for an authenticated user, false for one which isn't
	*/
	function is_authenticated($ident)
	{
		// If the ident is found in the userlist array, return true
		if(in_array($ident, $this->users))
		{
			$this->debug_message("User ($ident) is authenticated.");
			return 1;
		}
		else
		{
			$this->debug_message("User ($ident) is not authenticated.");
			return 0;
		}
	}

	/**
	 Joins a channel.
	 
	 @param string $channel The channels you wish the bot to join
	*/
	function join_channel($channel)
	{
		$this->send_data('JOIN', $channel);
	}

	/**
	 Parts (leaves) a channel.
	 
	 @param string $channel The channel you wish the bot to part
	*/
	function part_channel($channel)
	{
		$this->send_data('PART', $channel);
	}

	/**
	 OP's or de-OP's a user (depending on the boolean sent). If the bot has right to do so, it will give the specified user operator rights in the specified channel.
	 
	 @param string $channel The channel you wish to OP the user in
	 @param string $user The user you wish to OP
	 @param boolean $op Whether you wish to OP or de-OP the user
	*/
	function op_user($channel = '', $user = '', $op = true)
	{
		if($channel == '' || $user == '')
		{
			if($channel == '')
			{
				$channel = $this->ex['receiver'];
			}

			if($user == '')
			{
				$user = $this->ex['username'];
			}
		}

		if($op)
		{
			$this->send_data('MODE', $channel . ' +o ' . $user);
		}
		else
		{
			$this->send_data('MODE', $channel . ' -o ' . $user);
		}
	}
	
	/**
	 Prints a debug-message with a time-stamp.
	 
	 @param string $message The message to be printed
	*/
	function debug_message($message)
	{
		if(DEBUG)
		{
			echo "\r\n<br>[".date('h:i:s')."] ".$message;
		}
	}
}

?>