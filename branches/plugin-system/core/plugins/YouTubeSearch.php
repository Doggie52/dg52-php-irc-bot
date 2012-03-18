<?php
/**
 * YouTubeSearch plugin
 * 
 * Allows the user to do a basic YouTube search via channel and PM
 */

	class YouTubeSearch extends PluginEngine
	{
		/**
		 * Mandatory plugin properties
		 */
		public $PLUGIN_NAME = "YouTube Search";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Allows the user to perform YouTube searches.";
		public $PLUGIN_VERSION = "1.0";

		/**
		 * Properties
		 */
		private $resultLimit = 3;

		/** 
		 * Constructor
		 */
		public function __construct()
		{
			$this->register_command('yt', array('YouTubeSearch', 'do_search'));
			$this->register_command('youtube', array('YouTubeSearch', 'do_search'));
			$this->register_documentation('youtube', array('auth_level' => 0,
														'access_type' => 'both',
														'documentation' => array("*Usage:| !youtube <query> OR !yt <query>",
																				"Queries YouTube search for <query> and returns the results.")
														));
		}

		/**
		 * Determines what method to use when searching and displays the results
		 */
		public function do_search($data)
		{
			// Get the query
			$query = substr($data->fullLine, strlen('!'.$data->command.' '));

			// Checks whether cURL is loaded [temporarily disabled due to API not functioning]
			if(in_array('curl', get_loaded_extensions()))
			{
				$results = $this->youtube_search_api(array('q' => $query));
			}
			elseif(false)
			{
				$this->debug_message("cURL is not supported.");
			}

			// Store every result up to resultLimit in separate lines to be sent to the client
			$i = 1;
			foreach($results as $result)
			{
				$lines[] = "#".$i." *".$result->title->{'$t'}."* - __".$result->link[0]->href."__";
				$i++;
			}

			// Distinguish between PM and channel
			if($data->origin == Data::PM)
			{
				$_msg = new Message("PRIVMSG", "Results for +".$query."+:", $data->sender);
				foreach($lines as $line)
					$_msg = new Message("PRIVMSG", $line, $data->sender);
			}
			elseif($data->origin == Data::CHANNEL)
			{
				$_msg = new Message("PRIVMSG", "Results for +".$query."+:", $data->receiver);
				foreach($lines as $line)
					$_msg = new Message("PRIVMSG", $line, $data->receiver);
			}
		}

		/**
		 * Query YouTube Search API
		 *
		 *
		 * @access private
		 * @param array $args URL arguments. For most endpoints only "q" (query) is required.
		 * @param string $referer Referer to use in the HTTP header (must be valid).
		 * @return object or NULL on failure
		 */
		private function youtube_search_api($args, $referer = 'http://localhost/')
		{
			$url = "https://gdata.youtube.com/feeds/api/videos";

			// Sets necessary arguments
			if(!array_key_exists('alt', $args))
			{
				$args['alt'] = 'json';
			}
			if(!array_key_exists('max-results', $args))
			{
				$args['max-results'] = $this->resultLimit;
			}
			// Only return what's necessary
			if(!array_key_exists('fields', $args))
			{
				$args['fields'] = 'entry(title,link[@rel=\'alternate\'])';
			}

			$url .= '?'.http_build_query($args, '', '&');

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// Note that the referer *must* be set
			curl_setopt($ch, CURLOPT_REFERER, $referer);
			$body = curl_exec($ch);
			curl_close($ch);

			// Decode the response
			$results = json_decode($body);
			$results = $results->feed->entry;

			print_r($results);

			return $results;
		}

		/**
		 * [UNFINISHED] Query a regular YouTube search page and extract results from it.
		 *
		 * @access private
		 * @param string $query The search-query
		 * @param int $numresults The number of results to fetch (default: 3)
		 * @return mixed Either an array with the results or NULL if there are no results
		 */
		private function youtube_search_html($query, $numresults = 3)
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
				
	}
?>