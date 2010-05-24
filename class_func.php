<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 */

	/**
	 * Prints the bot's header with useful information and some nice ASCII text
	 *
	 * @return void
	 */
	function print_header()
	{
		$ascii = "
     _  ___ ___ ___ 
  __| |/ __| __|_  )
 / _` | (_ |__ \/ / 
 \__,_|\___|___/___|
  ___ _  _ ___ 
 | _ \ || | _ \
 |  _/ __ |  _/
 |_| |_||_|_|  
  ___ ___  ___ 
 |_ _| _ \/ __|
  | ||   / (__ 
 |___|_|_\\___|
  ___      _   
 | _ ) ___| |_ 
 | _ \/ _ \  _|
 |___/\___/\__|
			\n";
		if(GUI)
		{
			echo(nl2br($ascii));
		}
		else
		{
			echo($ascii);
		}
	}
	
	/**
	 * Sends data to the server. Important that basic structure of sent message is kept the same, otherwise it will fail.
	 * 
	 * @access public
	 * @param string $cmd The command you wish to send
	 * @param string $msg The parameters you wish to pass to the command (default: null)
	 * @param string $rcvr The receiver of the message (default: null)
	 * @return void
	 */
	function send_data($cmd, $msg = null, $rcvr = null)
	{
		// Fetch the socket
		global $socket;
		
		if($msg == null)
		{
			fputs($socket, $cmd."\r\n");
			debug_message("Command ($cmd) was sent to the server.");
		}
		elseif($cmd == "PRIVMSG")
		{
			fputs($socket, $cmd." ".$rcvr." :".$msg."\r\n");
			debug_message("Command ($cmd) with receiver ($rcvr) and message ($msg) was sent to the server.");
		}
		else
		{
			fputs($socket, $cmd." ".$msg."\r\n");
			debug_message("Command ($cmd) with message ($msg) was sent to the server.");
		}
	}
	
	/**
	 * Parses the raw commands sent by the server and splits them up into different parts, storing them in the $ex array for future use.
	 *
	 * @todo Put this function to use.
	 */
	function parse_raw_command()
	{
		
	}
	
	/**
	 * Interprets the receiver of a PRIVMSG sent and returns whether it is one from a user (a private message) or one sent in the channel.
	 * 
	 * @access public
	 * @param string $privmsg The message to be interpretted
	 * @return void
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
	 * @access public
	 * @param string $ident The username!hostname of the user we want to check
	 * @return boolean true for an authenticated user, false for one which isn't
	 */
	function is_authenticated($ident)
	{
		// Fetch the userlist array
		global $users;
		
		// If the ident is found in the userlist array, return true
		if(in_array($ident, $users))
		{
			debug_message("User ($ident) is authenticated.");
			return 1;
		}
		else
		{
			return 0;
		}
	}
	
	/**
	 * Reloads the arrays associated with speech
	 *
	 * @access public
	 * @return string $response The response-array
	 */
	function reload_speech()
	{
		if(isset($response))
		{
			unset($response);
		}
		// Include random responses
		include("speech.php");
		// Include info-keywords and their responses
		$keywordlist = file_get_contents("responses.inc");
		// Split each line into separate entry
		$line = explode("\n", $keywordlist);
		foreach($line as $keywordline)
		{
			// Get the first word in [0] and the rest in [1]
			$explode = explode(" ", $keywordline, 2);
			// Split them up!
			$response['info'][strtolower($explode[0])] = $explode[1];
		}
		
		debug_message("The speech array was successfully loaded into the system!");
		return $response;
	}
	
	/**
	 * Writes to the response-file.
	 *
	 * @access public
	 * @param string $line The line to write to the file
	 * @return void
	 */
	function write_response($line)
	{
		// Adds a newline to the beginning of the line to write
		$line = "\n".$line;
		file_put_contents("responses.inc", $line, FILE_APPEND);
	}

	/**
	 * Joins a channel. Checks if the channel-name includes a #-sign
	 *
	 * @access public
	 * @param string $channel The channels you wish the bot to join
	 * @return string $channel The channelname that was joined
	 */
	function join_channel($channel)
	{
		if($channel[0] != "#")
		{
			$channel = "#".$channel;
		}
		send_data("JOIN", $channel);
		debug_message("Channel ".$channel." was joined!");
		
		return $channel;
	}

	/**
	 * Parts (leaves) a channel. Checks if the channel-name includes a #-sign.
	 *
	 * @access public
	 * @param string $channel The channel you wish the bot to part
	 * @return string $channel The channelname that was parted
	 */
	function part_channel($channel)
	{
		if($channel[0] != "#")
		{
			$channel = "#".$channel;
		}
		send_data("PART", $channel);
		debug_message("Channel ".$channel." was parted!");
		
		return $channel;
	}
	
	/**
	 * Sets the topic of the specified channel.
	 * 
	 * @access public
	 * @param string $channel The channel you wish to change topic of
	 * @param string $topic The new topic to change to
	 * @return void
	 */
	function set_topic($channel, $topic)
	{
		send_data("TOPIC", $channel." :".$topic);
		debug_message("Channel topic for ".$channel." was altered to \"".$topic."\"!");
	}

	/**
	 * OP's or de-OP's a user (depending on the boolean sent). If the bot has right to do so, it will give the specified user operator rights in the specified channel.
	 *
	 * @access public
	 * @param string $channel The channel you wish to OP the user in (default: '')
	 * @param string $user The user you wish to OP (default: '')
	 * @param bool $op Whether you wish to OP or de-OP the user (default: true)
	 * @return void
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
			send_data("MODE", $channel . ' +o ' . $user);
		}
		else
		{
			send_data("MODE", $channel . ' -o ' . $user);
		}
	}
	
	/**
	 * Voices or de-voices a user (depending on the boolean sent). If the bot has right to do so, it will give the specified user voice in the specified channel.
	 *
	 * @access public
	 * @param string $channel The channel you wish to voice the user in (default: '')
	 * @param string $user The user you wish to voice (default: '')
	 * @param bool $op Whether you wish to voice or de-voice the user (default: true)
	 * @return void
	 */
	function voice_user($channel = '', $user = '', $op = true)
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
			send_data("MODE", $channel . ' +v ' . $user);
		}
		else
		{
			send_data("MODE", $channel . ' -v ' . $user);
		}
	}
	
	/**
	 * Prints a debug-message with a time-stamp.
	 *
	 * @access public
	 * @param string $message The message to be printed
	 * @return void
	 */
	function debug_message($message)
	{
		if(DEBUG)
		{
			if(GUI)
			{
				echo "\r\n<br>[".@date('h:i:s')."] ".$message;
			}
			else
			{
				echo "\r\n[".@date('h:i:s')."] ".$message;
			}
		}
	}

?>