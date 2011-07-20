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
	 * Sends data to the server. Important that basic structure of sent message is kept the same, otherwise it will fail.
	 * 
	 * @access public
	 * @param string $cmd The command you wish to send
	 * @param string $msg The parameters you wish to pass to the command (default: null)
	 * @param string $rcvr The receiver of the message (default: null)
	 * @return boolean Whether the data was sent successfully
	 */
	function send_data($cmd, $msg = null, $rcvr = null)
	{
		// Fetch the socket
		global $socket;
		
		if($msg == null)
		{
			if(fputs($socket, $cmd."\r\n"))
			{
				if(DEBUG_OUTPUT)
				{
					debug_message("Command ($cmd) was sent to the server.");
				}
				return TRUE;
			}
		}
		elseif($cmd == "PRIVMSG")
		{
			if(fputs($socket, $cmd." ".$rcvr." :".$msg."\r\n"))
			{
				if(DEBUG_OUTPUT)
				{
					debug_message("Command ($cmd) with receiver ($rcvr) and message ($msg) was sent to the server.");
				}
				return TRUE;
			}
		}
		else
		{
			if(fputs($socket, $cmd." ".$msg."\r\n"))
			{
				if(DEBUG_OUTPUT)
				{
					debug_message("Command ($cmd) with message ($msg) was sent to the server.");
				}
				return TRUE;
			}
		}
	}
	
	/**
	 * Removes an item from an array by its value
	 * Inspired by http://dev-tips.com/featured/remove-an-item-from-an-array-by-value
	 * 
	 * @param string $value The value to remove
	 * @param array $array The array to remove the value from
	 * @return array $array The modified array
	 */
	function remove_item_by_value($value, $array)
	{
		if(!in_array($value, $array))
		{
			return $array;
		}
		
		foreach($array as $key => $avalue)
		{
			if ($avalue == $value)
			{
				unset($array[$key]);
			}
		}
		return $array;
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