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
	
	/**
	 * [UNFINISHED] Query a regular YouTube search page and extract results from it.
	 *
	 * @access public
	 * @param string $query The search-query
	 * @param int $numresults The number of results to fetch (default: 3)
	 * @return mixed Either an array with the results or NULL if there are no results
	 */
	function youtube_search_html($query, $numresults = 3)
	{
		$off_site = "http://www.youtube.com/results?search_query=".urlencode($query)."&ie=UTF-8&oe=UTF-8";
		$buf = file_get_contents($off_site) or die("Unable to grab contents.");
		// Get rid of highlights and linebreaks along with other tags
		$buf = str_replace("<em>", "", $buf);
		$buf = str_replace("</em>", "", $buf);
		$buf = str_replace("<b>", "", $buf);
		$buf = str_replace("</b>", "", $buf);
		// YouTube likes whitespaces - regex does not
		$buf = str_replace("\t", "", $buf);
		$buf = str_replace("\n", "", $buf);
		// Define patterns
		$videopattern = "/(?:<div class=\"result-item-main-content\">)(?:.*?)(?:href=\")(.*?)(?:\")(?:.*?)(?:dir=\"ltr\")(?:.*?)(?:\")(.*?)(?:\")/i";
		// $titlepattern = "/(?:<div class=\"video-long-title\">)(?:.*?)(?:title=\")(.*?)(?:\")/i"; -- no longer valid
		$descriptionpattern = "/(?:class=\"video-description\">)(.*?)(?:<\/div>)/i";
		// Match the raw HTML with the patterns
		// $videos: [1] is the URL, [2] is the title
		preg_match_all($urlpattern, $buf, $videos);
		// preg_match_all($titlepattern, $buf, $titles);
		preg_match_all($descriptionpattern, $buf, $descriptions);
		
		// Find the results, if there are any
		if($videos)
		{
			// Initiate counter for amount of search results found
			$i = 1;
			foreach($videos[1] as $url)
			{
				if($i <= $numresults)
				{
					$result[$i]['id'] = $i;
					$result[$i]['url'] = "http://www.youtube.com".html_entity_decode(htmlspecialchars_decode($url, ENT_QUOTES), ENT_QUOTES);
					$i++;
				}
			}
			$i = 1;
			foreach($videos[2] as $title)
			{
				if($i <= $numresults)
				{
					$result[$i]['title'] = html_entity_decode(htmlspecialchars_decode($title, ENT_QUOTES), ENT_QUOTES);
					$i++;
				}
			}
			$i = 1;
			foreach($descriptions[1] as $description)
			{
				if($i <= $numresults)
				{
					$result[$i]['description'] = html_entity_decode(htmlspecialchars_decode($description, ENT_QUOTES), ENT_QUOTES);
					$i++;
				}
			}
			return $result;
		}
		
		// If no results are found, return nothing
		return;
	}

?>