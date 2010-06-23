<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 */

	/**
	 * Gets the latest revision of an SVN repository with a general HTML output
	 * 
	 * @access public
	 * @param string $site The URI of the repository (with http://)
	 * @return string $revision The revision number extracted
	 */
	function get_latest_rev($site)
	{
		$raw = file_get_contents($site);
		$regex = "/(Revision)(\\s+)(\\d+)(:)/is";
		preg_match_all($regex, $raw, $match);
		$revision = $match[3][0];
		
		return $revision;
	}
	
	/**
	 * Prints the bot's header with useful information and some nice ASCII text
	 *
	 * @return void
	 */
	function print_header()
	{
		$svnrev = get_latest_rev("http://dg52-php-irc-bot.googlecode.com/svn/trunk/");
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
	 * Prints general information about the bot along with uptime to a user.
	 * 
	 * @access public
	 * @param string $username The username the info should be sent to
	 * @param string $starttime The start-time of the script in UNIX-timestamp format
	 * @return void
	 */
	function print_info($username, $starttime)
	{
		send_data("PRIVMSG", "dG52's PHP IRC Bot r".get_latest_rev("http://dg52-php-irc-bot.googlecode.com/svn/trunk/"), $username);
		// For UNIX-based systems, model and load can be displayed
		if(PHP_OS != "WINNT" && PHP_OS != "WIN32")
		{
			send_data("PRIVMSG", "  Server OS: ".PHP_OS." (".php_uname().")", $username);
			$hwmodel = substr(exec('sysctl hw.model'), 9);
			send_data("PRIVMSG", "  Server CPU model: ".$hwmodel, $username);
			$uptime = exec('uptime');
			if($c = preg_match_all('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/', $uptime, $matches) > 0)
			{
				send_data("PRIVMSG", "  Average server load (past 1 minute): ".$matches[1][0], $username);
				send_data("PRIVMSG", "  Average server load (past 5 minutes): ".$matches[2][0], $username);
				send_data("PRIVMSG", "  Average server load (past 15 minutes): ".$matches[3][0], $username);
			}
		}
		else
		{
            send_data("PRIVMSG", "  Server OS: ".strtolower(PHP_OS), $username);
		}
		$currtime = time();
		$seconds = $currtime - $starttime;
		$minutes = bcmod((intval($seconds) / 60),60);
		$hours = intval(intval($seconds) / 3600);
		$seconds = bcmod(intval($seconds),60);
		send_data("PRIVMSG", "  Bot uptime: ".$hours." hours ".$minutes." minutes ".$seconds." seconds.", $username);
		debug_message("Info was sent to ".$username."!");
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
	 * @param string $data The raw data sent by the socket
	 * @return array $ex
	 */
	function parse_raw_command($data)
	{
		// Explodes the raw data into an initial array
		$ex				= explode(" ", $data);
		// Get length of everything before command including last space
		$identlength		= strlen($ex[0]." ".(isset($ex[1]) ? $ex[1] : "")." ".(isset($ex[2]) ? $ex[2] : "")." ");
		// Retain all that is in $data after $identlength characters with replaced chr(10)'s and chr(13)'s and minus the first ':'
		$rawcommand		= substr($data, $identlength);
		$ex['fullcommand']	= substr(str_replace(array(chr(10), chr(13)), '', $rawcommand), 1);
		// Split the commandstring up into a second array with words
		$ex['command']		= explode(" ", $ex['fullcommand']);
		// The username!hostname of the sender (don't include the first ':' - start from 1)
		$ex['ident']		= substr($ex[0], 1);
		// Only the username of the sender (one step extra because only that before the ! wants to be parsed)
		$hostlength		= strlen(strstr($ex[0], '!'));
		$ex['username']	= substr($ex[0], 1, -$hostlength);
		// The receiver of the sent message (either the channelname or the bots nickname)
		$ex['receiver']	= (isset($ex[2]) ? $ex[2] : "");
		// Interpret the type of message received ("PRIVATE" or "CHANNEL") depending on the receiver
		$ex['type']		= interpret_privmsg($ex['receiver']);
		
		return $ex;
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
	 * Looks up the specified command's help
	 * 
	 * @access public
	 * @param string $command The command specified
	 * @param string $commandarray The array of commands and their respective help entries
	 * @param string $username The username that requests the help
	 * @return void
	 */
	function lookup_help($command, $commandarray, $username)
	{
		$command = strtolower($command);
		// If the user is not looking for specific support for a command
		if(!$command)
		{
			send_data("PRIVMSG", format_text("bold", "COMMANDS AVAILABLE VIA PM"), $username);
			$commandlist = "";
			foreach($commandarray as $commandname => $commandusage)
			{
				$commandlist .= "!".$commandname.", ";
			}
			send_data("PRIVMSG", substr($commandlist, 0, -2), $username);
			send_data("PRIVMSG", "Type !help <command> to see the corresponding syntax.", $username);
		}
		else
		{
			// If the command looked up exists in the documentation
			if(array_key_exists($command, $commandarray))
			{
				foreach($commandarray[$command] as $usage)
				{
					send_data("PRIVMSG", $usage, $username);
				}
			}
			else
			{
				send_data("PRIVMSG", "Command \"".$command."\" was not defined in the documentation!", $username);
			}
		}
	}
	
	/**
	 * Checks if sender of message is in the list of authenticated users. (username!hostname)
	 * 
	 * @access public
	 * @param string $ident The username!hostname of the user we want to check
	 * @return boolean $authenticated true for an authenticated user, false for one which isn't
	 */
	function is_authenticated($ident)
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
	 * @param array $commandarray The array of commands and their respective help entries
	 * @return bool $successs Whether the write was successful or not
	 */
	function write_response($line, $commandarray)
	{
	     if($line)
	     {
	          if($line[0] != " ")
	          {
				$linearray = explode(" ", $line);
				// Does there exist a definition?
				if($linearray[1])
				{
					if(!array_key_exists($linearray[0], $commandarray))
					{
						$line = "\n".$line;
						// Appends the new line only if it meets the following: existant, not blankspace and unique
						file_put_contents("responses.inc", $line, FILE_APPEND);
						$success = 1;
					}
				}
			}
		}
		else
		{
			$success = 0;
		}
		return $success;
	}
	
	/**
	 * Converts input to a proper channel-name if it isn't already
	 *
	 * @access public
	 * @param string $channel The channelname to be converted
	 * @return string $channel The converted channel
	 */
	function to_channel($channel)
	{
          if($channel[0] != "#")
		{
			$channel = "#".$channel;
		}
		return $channel;
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
		$channel = to_channel($channel);
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
		$channel = to_channel($channel);
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
	     $channel = to_channel($channel);
		send_data("TOPIC", $channel." :".$topic);
		debug_message("Channel topic for ".$channel." was altered to \"".$topic."\"!");
	}

	/**
	 * OP's or de-OP's a user (depending on the boolean sent). If the bot has right to do so, it will give the specified user operator rights in the specified channel.
	 *
	 * @access public
	 * @param array $ex The full command-array used to grab either the receiver or the username
	 * @param bool $op Whether you wish to OP or de-OP the user (default: true)
	 * @return void
	 */
	function op_user($ex, $op = true)
	{
	     $channel = to_channel($ex['command'][1]);
	     // If the username is not supplied assume it is identical to that which the PM originated from
		if(!isset($ex['command'][2]))
		{
			$user = $ex['username'];
		}
		else
		{
               $user = $ex['command'][2];
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
      * @param array $ex The full command-array used to grab either the receiver or the username
	 * @param bool $voice Whether you wish to voice or de-voice the user (default: true)
	 * @return void
	 */
	function voice_user($ex, $voice = true)
	{
		$channel = to_channel($ex['command'][1]);
	     // If the username is not supplied assume it is identical to that which the PM originated from
		if(!isset($ex['command'][2]))
		{
			$user = $ex['username'];
		}
		else
		{
               $user = $ex['command'][2];
		}
		
		if($voice)
		{
			send_data("MODE", $channel . ' +v ' . $user);
		}
		else
		{
			send_data("MODE", $channel . ' -v ' . $user);
		}
	}
	
	/**
	 * Formats the text according to an array of different styles
	 * 
	 * @access public
	 * @param mixed $styles The styles to format the text with (choose between bold, italic or underline)
	 * @param string $message The message to format
	 * @return string $message The formatted message
	 */
	function format_text($styles, $message)
	{
		// If the input is not an array, explode it into one
		if(!is_array($styles))
		{
			$styles = explode(" ", $styles);
		}
		
		if(in_array("bold", $styles))
		{
			// Send STX for bold
			$message = chr(2).$message;
		}
		
		if(in_array("italic", $styles))
		{
			// Send SYN for italic
			$message = chr(22).$message;
		}
		
		if(in_array("underline", $styles))
		{
			// Send US for underline
			$message = chr(31).$message;
		}
		
		// Send SI for end of message
		$message = $message.chr(15);
		
		return $message;
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
				$newpath = preg_replace("/%date%/", @date('Ymd'), LOG_PATH);
				file_put_contents($newpath, "\r\n[".@date('h:i:s')."] ".$message, FILE_APPEND);
			}
		}
	}

?>