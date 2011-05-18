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
		$minutes = floor($seconds / 60);
		$hours = floor($seconds / 3600);
		$seconds = $seconds;
		send_data("PRIVMSG", "  Bot uptime: ".$hours." hours ".$minutes." minutes ".$seconds." seconds.", $username);
		debug_message("Info was sent to ".$username."!");
	}
	
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
	 * Sets a certain mode on the specified user if the bot has the right to do this.
	 *
	 * @access public
	 * @param array $ex The full command-array used to grab either the receiver or the username
	 * @param bool $mode The desired mode
	 * @return void
	 */
	function mode_user($ex, $mode)
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
		
		if($mode)
		{
			send_data("MODE", $channel." ".$mode." ".$user);
		}
	}
	
	/**
	 * Query Google AJAX Search API. All credits to http://w-shadow.com/blog/2009/01/05/get-google-search-results-with-php-google-ajax-api-and-the-seo-perspective/.
	 *
	 * @todo Implement this function, checking if curl is enabled or not and test the function itself
	 *
	 * @access public
	 * @param array $args URL arguments. For most endpoints only "q" (query) is required.
	 * @param string $referer Referer to use in the HTTP header (must be valid).
	 * @param string $endpoint API endpoint. Defaults to 'web' (web search).
	 * @return object or NULL on failure
	 */
	function google_search_api($args, $referer = 'http://localhost/', $endpoint = 'web')
	{
		$url = "http://ajax.googleapis.com/ajax/services/search/".$endpoint;
		if (!array_key_exists('v', $args))
		{
			$args['v'] = '1.0';
		}
		$url .= '?'.http_build_query($args, '', '&');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// note that the referer *must* be set
		curl_setopt($ch, CURLOPT_REFERER, $referer);
		$body = curl_exec($ch);
		curl_close($ch);
		//decode and return the response
		return json_decode($body);
	}
	
	/**
	 * Query a regular Google search page and extract results from it.
	 *
	 * @access public
	 * @param string $query The search-query
	 * @param int $numresults The number of results to fetch (default: 3)
	 * @return mixed Either an array with the results or NULL if there are no results
	 */
	function google_search_html($query, $numresults = 3)
	{
		$off_site = "http://www.google.com/search?q=".urlencode($query)."&ie=UTF-8&oe=UTF-8";
		$buf = file_get_contents($off_site) or die("Unable to grab contents.");
		// Get rid of highlights and linebreaks along with other tags
		$buf = str_replace("<em>", "", $buf);
		$buf = str_replace("</em>", "", $buf);
		$buf = str_replace("<b>", "", $buf);
		$buf = str_replace("</b>", "", $buf);
		$buf = str_replace("<nobr>", "", $buf);
		$buf = str_replace("</nobr>", "", $buf);
		$buf = str_replace("<div class=\"f\">", "", $buf);
		$buf = str_replace("</div>", " ", $buf);
		// Define patterns
		$urlpattern = "/(?:<h3 class=\"r\"><a href=\")(.*?)(?:\")/i";
		$titlepattern = "/(?:<h3 class=\"r\"><a href=\")(?:.*?)(?:\" class=l>)(.*?)(?:<\/a>)/i";
		$descriptionpattern = "/(?:<div class=\"s\">)(.*?)(?:<br>)/i";
		// Match the raw HTML with the patterns
		preg_match_all($urlpattern, $buf, $urls);
		preg_match_all($titlepattern, $buf, $titles);
		preg_match_all($descriptionpattern, $buf, $descriptions);
		
		// Find the results, if there are any
		if($urls && $titles)
		{
			// Initiate counter for amount of search results found
			$i = 1;
			foreach($urls[1] as $url)
			{
				if($i <= $numresults)
				{
					$result[$i]['id'] = $i;
					$result[$i]['url'] = html_entity_decode(htmlspecialchars_decode($url, ENT_QUOTES), ENT_QUOTES);
					$i++;
				}
			}
			$i = 1;
			foreach($titles[1] as $title)
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