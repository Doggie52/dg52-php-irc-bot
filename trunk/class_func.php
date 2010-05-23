<?php
	/*
		dG52 PHP IRC Bot

		Author: Douglas Stridsberg
			Email: doggie52@gmail.com
			URL: www.douglasstridsberg.com
	*/

	/**
	 * Checks current microtime.
	 * http://www.developertutorials.com/blog/php/php-measure-max-execution-time-script-execution-time-83/
	 */
	function microtime_float()
	{
		list($utime, $time) = explode(" ", microtime());
		return ((float)$utime + (float)$time);
	}

	/**
	 * Sends data to the server. Important that basic structure of sent message is kept the same, otherwise it will fail.
	 *
	 * @param string $cmd The command you wish to send
	 * @param string $msg The parameters you wish to pass to the command
	 * @param string $rcvr The receiver of the message
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
	 * @param string $privmsg The message to be interpretted	 
	 * @return string $message The channel and/or the username of the sender
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
			debug_message("The received message was of type [".$message."]!");
			return $message;
		}
		// Or if the sent message's receiver is the bots nickname
		elseif($privmsg == BOT_NICKNAME)
		{
			// ... it is a private message
			$message = "PRIVATE";
			debug_message("The received message was of type [".$message."]!");
			return $message;
		}
	}
	
	/**
	 * Checks if sender of message is in the list of authenticated users. (username!hostname)
	 *
	 * @param string $ident The username!hostname of the user we want to check
	 * @param boolean true for an authenticated user, false for one which isn't
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
			debug_message("User ($ident) is not authenticated.");
			return 0;
		}
	}
	
	/**
	 * Reloads the arrays associated with speech
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
		$file = fopen("responses.inc", "r");
		$keywordlist = fread($file, filesize("responses.inc"));
		fclose($file);
		// Split each line into separate entry
		$line = explode("\n", $keywordlist);
		foreach($line as $keywordline)
		{
			// Get the first word in [0] and the rest in [1]
			$explode = explode(" ", $keywordline, 2);
			// Split them up!
			$response['info'][$explode[0]] = $explode[1];
		}
		
		debug_message("Speech was successfully reloaded!");
		return $response;
	}

	/**
	 * Joins a channel. Checks if the channel-name includes a #-sign
	 *
	 * @param string $channel The channels you wish the bot to join
	 */
	function join_channel($channel)
	{
		if($channel[0] != "#")
		{
			$channel = "#".$channel;
		}
		send_data('JOIN', $channel);
		debug_message("Channel ".$channel." was joined!");
		
		return $channel;
	}

	/**
	 * Parts (leaves) a channel. Checks if the channel-name includes a #-sign
	 *
	 * @param string $channel The channel you wish the bot to part
	 */
	function part_channel($channel)
	{
		if($channel[0] != "#")
		{
			$channel = "#".$channel;
		}
		send_data('PART', $channel);
		debug_message("Channel ".$channel." was parted!");
		
		return $channel;
	}

	/**
	 * OP's or de-OP's a user (depending on the boolean sent). If the bot has right to do so, it will give the specified user operator rights in the specified channel.
	 *
	 * @param string $channel The channel you wish to OP the user in
	 * @param string $user The user you wish to OP
	 * @param boolean $op Whether you wish to OP or de-OP the user
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
			send_data('MODE', $channel . ' +o ' . $user);
		}
		else
		{
			send_data('MODE', $channel . ' -o ' . $user);
		}
	}
	
	/**
	 * Voices or de-voices a user (depending on the boolean sent). If the bot has right to do so, it will give the specified user voice in the specified channel.
	 *
	 * @param string $channel The channel you wish to voice the user in
	 * @param string $user The user you wish to voice
	 * @param boolean $op Whether you wish to voice or de-voice the user
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
			send_data('MODE', $channel . ' +v ' . $user);
		}
		else
		{
			send_data('MODE', $channel . ' -v ' . $user);
		}
	}
	
	/**
	 * Prints a debug-message with a time-stamp.
	 *
	 * @param string $message The message to be printed
	 */
	function debug_message($message)
	{
		if(DEBUG)
		{
			echo "\r\n<br>[".date('h:i:s')."] ".$message;
		}
	}

?>