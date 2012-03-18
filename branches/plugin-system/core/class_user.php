<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Functions that are connected to user input
	 */
	
	/**
	 * Looks up the specified command's help
	 * 
	 * @access public
	 * @param string $command The command specified
	 * @param string $commandarray The array of commands and their respective help entries
	 * @param string $username The username that requests the help
	 * @return bool False if no definition was found for the command
	 */
	function lookup_help($command, $commandarray, $username)
	{
		$command = strtolower($command);
		// If the user is not looking for specific support for a command
		if(!$command)
		{
			$commandlist = "";
			// For all PM commands
			send_data("PRIVMSG", format_text("bold", "COMMANDS AVAILABLE VIA PM"), $username);
			foreach($commandarray['pm'] as $commandname => $commandusage)
			{
				$commandlist .= "!".$commandname.", ";
			}
			send_data("PRIVMSG", substr($commandlist, 0, -2), $username);
			$commandlist = "";
			// For all channel commands
			send_data("PRIVMSG", format_text("bold", "COMMANDS AVAILABLE VIA CHANNEL"), $username);
			foreach($commandarray['channel'] as $commandname => $commandusage)
			{
				$commandlist .= "!".$commandname.", ";
			}
			send_data("PRIVMSG", substr($commandlist, 0, -2), $username);
			return 1;
		}
		else
		{
			// If the command looked up exists in the documentation (either in PM or channel)
			if(array_key_exists($command, $commandarray['pm']))
			{
				foreach($commandarray['pm'][$command] as $usage)
				{
					send_data("PRIVMSG", $usage, $username);
				}
			}
			elseif(array_key_exists($command, $commandarray['channel']))
			{
				foreach($commandarray['channel'][$command] as $usage)
				{
					send_data("PRIVMSG", $usage, $username);
				}
			}
			else
			{
				return 0;
			}
		}
	}

?>