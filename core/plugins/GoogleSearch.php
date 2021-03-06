<?php
	/**
	 * GoogleSearch plugin
	 *
	 * Allows the user to do a basic Google search via channel and PM
	 *
	 * REQUIRES: PHP cURL extension
	 *
	 * @todo Respond to mentions of 'google' in channel chat, with instructions on how to use the command
	 */

	class GoogleSearch extends PluginEngine
	{
		/**
		 * Mandatory plugin properties
		 */
		public $PLUGIN_NAME = "Google Search";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Allows the user to perform Google searches.";
		public $PLUGIN_VERSION = "1.0";

		/**
		 * Properties
		 */
		private $resultLimit = 3;
		private $cache;

		/**
		 * Constructor
		 */
		public function __construct()
		{
			$this->register_command( 'google', array( 'GoogleSearch', 'do_search' ) );
			$this->register_documentation( 'google', array( 'auth_level' => 0,
				'access_type' => 'both',
				'documentation' => array( "*Usage:| !google <query>",
					"Queries Google search for <query> and returns the results." )
			) );

			// Initialize cache object
			$this->cache = DiskCache::getInstance();
		}

		/**
		 * Determines what method to use when searching and displays the results
		 */
		public function do_search( $data )
		{
			// Get the query
			$query = substr( $data->fullLine, strlen( '!' . $data->command . ' ' ) );

			// Checks whether cURL is loaded
			if ( in_array( 'curl', get_loaded_extensions() ) ) {
				if ( !( $results = $this->google_search_api( array( 'q' => $query ) ) ) )
				{
					$_msg = new Message( "PRIVMSG", "No results found! Either your quota is exceeded or there were no results.", $data->sender );
					return false;
				}
			}
			else
			{
				$this->debug_message( "cURL is not supported." );
				return false;
			}

			// Store every result up to resultLimit in separate lines to be sent to the client
			$i = 1;
			foreach ( $results as $result )
			{
				if ( $i <= $this->resultLimit ) {
					$lines[] = "#" . $i . " *" . $result->titleNoFormatting . "* - __" . $result->url . "__";
					$i++;
				}
			}

			// Distinguish between PM and channel
			if ( $data->origin == Data::PM ) {
				$_msg = new Message( "PRIVMSG", "Results for +" . $query . "+:", $data->sender );
				foreach ( $lines as $line )
					$_msg = new Message( "PRIVMSG", $line, $data->sender );
			}
			elseif ( $data->origin == Data::CHANNEL )
			{
				$_msg = new Message( "PRIVMSG", "Results for +" . $query . "+:", $data->receiver );
				foreach ( $lines as $line )
					$_msg = new Message( "PRIVMSG", $line, $data->receiver );
			}
		}

		/**
		 * Query Google AJAX Search API. All credits to http://w-shadow.com/blog/2009/01/05/get-google-search-results-with-php-google-ajax-api-and-the-seo-perspective/.
		 *
		 *
		 * @access private
		 * @param array $args URL arguments. For most endpoints only "q" (query) is required.
		 * @param string $referer Referer to use in the HTTP header (must be valid).
		 * @return object $results Result object or NULL on failure
		 */
		private function google_search_api( $args, $referer = 'http://localhost/' )
		{
			// Is this cached?
			if ( isset( $this->cache->{'google_search__' . $args['q']} ) )
				return $this->cache->{'google_search__' . $args['q']};

			$url = "https://ajax.googleapis.com/ajax/services/search/web";

			// Sets necessary arguments
			if ( !array_key_exists( 'v', $args ) ) {
				$args['v'] = '1.0';
			}

			$url .= '?' . http_build_query( $args, '', '&' );

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			// Note that the referer *must* be set
			curl_setopt( $ch, CURLOPT_REFERER, $referer );
			// Fixes lack of response on Windows machines
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
			$body = curl_exec( $ch );
			curl_close( $ch );

			// Decode the response
			$results = json_decode( $body );

			// Was the search successful?
			if ( is_object( $results->responseData ) ) {
				$results = $results->responseData->results;

				// Cache it!
				$this->cache->{'google_search__' . $args['q']} = $results;

				return $results;
			}
			else
				return false;
		}

		/**
		 * Query a regular Google search page and extract results from it.
		 *
		 * @todo Get regex patterns working again
		 *
		 * @access private
		 * @param string $query The search-query
		 * @param int $numresults The number of results to fetch (default: 3)
		 * @return mixed Either an array with the results or NULL if there are no results
		 */
		private function google_search_html( $query, $numresults = 3 )
		{
			$off_site = "http://www.google.com/search?q=" . urlencode( $query ) . "&ie=UTF-8&oe=UTF-8";
			$buf = file_get_contents( $off_site ) or die( "Unable to grab contents." );
			// Get rid of highlights and linebreaks along with other tags
			$buf = str_replace( "<em>", "", $buf );
			$buf = str_replace( "</em>", "", $buf );
			$buf = str_replace( "<b>", "", $buf );
			$buf = str_replace( "</b>", "", $buf );
			$buf = str_replace( "<nobr>", "", $buf );
			$buf = str_replace( "</nobr>", "", $buf );
			$buf = str_replace( "<div class=\"f\">", "", $buf );
			$buf = str_replace( "</div>", " ", $buf );
			// Define patterns [URL and title are not working, URL gives additional data and title just doesn't match]
			$urlpattern = "/(?:<h3 class=\"r\"><a href=\")(.*?)(?:\")/i";
			$titlepattern = "/(?:<h3 class=\"r\"><a href=\")(?:.*?)(?:\" class=l>)(.*?)(?:<\/a>)/i";
			$descriptionpattern = "/(?:<div class=\"s\">)(.*?)(?:<br>)/i";
			// Match the raw HTML with the patterns
			preg_match_all( $urlpattern, $buf, $urls );
			preg_match_all( $titlepattern, $buf, $titles );
			preg_match_all( $descriptionpattern, $buf, $descriptions );

			// Find the results, if there are any
			if ( $urls && $titles ) {
				// Initiate counter for amount of search results found
				$i = 1;
				foreach ( $urls[1] as $url )
				{
					if ( $i <= $numresults ) {
						$result[$i]['id'] = $i;
						$result[$i]['url'] = html_entity_decode( htmlspecialchars_decode( $url, ENT_QUOTES ), ENT_QUOTES );
						$i++;
					}
				}
				$i = 1;
				foreach ( $titles[1] as $title )
				{
					if ( $i <= $numresults ) {
						$result[$i]['title'] = html_entity_decode( htmlspecialchars_decode( $title, ENT_QUOTES ), ENT_QUOTES );
						$i++;
					}
				}
				$i = 1;
				foreach ( $descriptions[1] as $description )
				{
					if ( $i <= $numresults ) {
						$result[$i]['description'] = html_entity_decode( htmlspecialchars_decode( $description, ENT_QUOTES ), ENT_QUOTES );
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