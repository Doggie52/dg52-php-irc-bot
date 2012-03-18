<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Functions concerning the bots internal workings
	 */
	
	/**
	 * Gets the latest revision of an SVN repository with a general HTML output.
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
	 * Prints the bot's header with useful information and some nice ASCII text.
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
			if(DEBUG_OUTPUT)
			{
				debug_message("Command ($cmd) was sent to the server.");
			}
		}
		elseif($cmd == "PRIVMSG")
		{
			fputs($socket, $cmd." ".$rcvr." :".$msg."\r\n");
			if(DEBUG_OUTPUT)
			{
				debug_message("Command ($cmd) with receiver ($rcvr) and message ($msg) was sent to the server.");
			}
		}
		else
		{
			fputs($socket, $cmd." ".$msg."\r\n");
			if(DEBUG_OUTPUT)
			{
				debug_message("Command ($cmd) with message ($msg) was sent to the server.");
			}
		}
	}
	
	/**
	 * Parses the raw commands sent by the server and splits them up into different parts, storing them in the $ex array for future use.
	 *
	 * @param string $data The raw data sent by the socket
	 * @return array $ex The parsed raw command, split into an array
	 */
	function parse_raw_command($data)
	{
		// Explodes the raw data into an initial array
		$ex					= explode(" ", $data);
		// Get length of everything before command including last space
		$identlength		= strlen($ex[0]." ".(isset($ex[1]) ? $ex[1] : "")." ".(isset($ex[2]) ? $ex[2] : "")." ");
		// Retain all that is in $data after $identlength characters with replaced chr(10)'s and chr(13)'s and minus the first ':'
		$rawcommand			= substr($data, $identlength);
		$ex['fullcommand']	= substr(str_replace(array(chr(10), chr(13)), '', $rawcommand), 1);
		// Split the commandstring up into a second array with words
		$ex['command']		= explode(" ", $ex['fullcommand']);
		// The username!hostname of the sender (don't include the first ':' - start from 1)
		$ex['ident']		= substr($ex[0], 1);
		// Only the username of the sender (one step extra because only that before the ! wants to be parsed)
		$hostlength			= strlen(strstr($ex[0], '!'));
		$ex['username']		= substr($ex[0], 1, -$hostlength);
		// The receiver of the sent message (either the channelname or the bots nickname)
		$ex['receiver']		= (isset($ex[2]) ? $ex[2] : "");
		// Interpret the type of message received ("PRIVATE" or "CHANNEL") depending on the receiver
		$ex['type']			= interpret_privmsg($ex['receiver']);
		
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
	 * Checks if sender of message is in the list of authenticated users. (username!hostname)
	 * 
	 * @access public
	 * @param string $ident The username!hostname of the user we want to check
	 * @return boolean $authenticated TRUE for an authenticated user, FALSE for one which isn't
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
	 * (re)Loads the array of administrators
	 *
	 * @access public
	 * @return array $users The array of administrators
	 */
	function reload_users()
	{
		// Open the users.inc file
		$file = fopen(USERS_PATH, "r");
		// Turn the hostnames into lowercase (does not compromise security, as hostnames are unique anyway)
		$userlist = strtolower(fread($file, filesize(USERS_PATH)));
		fclose($file);
		// Split each line into separate entry in the returned array
		$users = explode("\n", $userlist);
		debug_message("The list of administrators was successfully loaded into the system!");
		return $users;
	}
	
	/**
	 * Reloads the arrays associated with speech
	 *
	 * @access public
	 * @return array $response The response-array
	 */
	function reload_speech()
	{
		if(isset($response))
		{
			unset($response);
		}
		// Include random responses
		include("extra/speech.php");
		// Include definitions and their responses
		$definitionlist = file_get_contents(DEFINITION_PATH);
		// Split each line into separate entry
		$line = explode("\n", $definitionlist);
		foreach($line as $definitionline)
		{
			// Get the first word in [0] and the rest in [1]
			$explode = explode(" ", $definitionline, 2);
			// Split them up!
			$response['info'][strtolower($explode[0])] = $explode[1];
		}
		debug_message("The speech and definition arrays were successfully loaded into the system!");
		return $response;
	}
	
	/**
	 * Writes to the list of keywords and their definitions
	 *
	 * @access public
	 * @param string $line The line to write to the file
	 * @param array $commandarray The array of commands and their respective help entries
	 * @return bool $successs Whether the write was successful or not
	 */
	function write_definition($line, $commandarray)
	{
		if(isset($line) && $line != "list")
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
						file_put_contents(DEFINITION_PATH, $line, FILE_APPEND);
						$success = 1;
						debug_message("\"".$line."\" was written to the list of definitions!");
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
			// Send GS for italic
			$message = chr(29).$message;
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
		    $line = "[".@date('h:i:s')."] ".$message."\r\n";
			if(GUI)
			{
				echo "<br>".$line;
			}
			else
			{
				echo $line;
			}
			$newpath = preg_replace("/%date%/", @date('Ymd'), LOG_PATH);
			file_put_contents($newpath, $line, FILE_APPEND);
		}
	}

?>