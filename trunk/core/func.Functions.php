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
	 * remove_item_by_value()
	 *
	 * @abstract Removes an item from an array by its value.
	 * Inspired by http://dev-tips.com/featured/remove-an-item-from-an-array-by-value.
	 *
	 * @param string $value The value to remove.
	 * @param array $array The array to remove the value from.
	 * @return array $array The modified array.
	 */
	function remove_item_by_value( $value, $array )
	{
		if ( !in_array( $value, $array ) ) {
			return $array;
		}

		foreach ( $array as $key => $avalue )
		{
			if ( $avalue == $value ) {
				unset( $array[$key] );
			}
		}
		return $array;
	}

	/**
	 * reload_speech()
	 *
	 * @abstract Reloads the arrays associated with speech.
	 *
	 * @return array $response The response-array.
	 */
	function reload_speech()
	{
		if ( isset( $response ) ) {
			unset( $response );
		}
		// Include random responses
		include( "extra/speech.php" );
		// Include definitions and their responses
		$definitionlist = file_get_contents( DEFINITION_PATH );
		// Split each line into separate entry
		$line = explode( "\n", $definitionlist );
		foreach ( $line as $definitionline )
		{
			// Get the first word in [0] and the rest in [1]
			$explode = explode( " ", $definitionline, 2 );
			// Split them up!
			$response['info'][strtolower( $explode[0] )] = $explode[1];
		}
		debug_message( "The speech and definition arrays were successfully loaded into the system!" );
		return $response;
	}

	/**
	 * get_latest_rev()
	 *
	 * @abstract Gets the latest revision of an SVN repository with a general HTML output.
	 *
	 * @param string $site The URI of the repository (with http://).
	 * @return string $revision The revision number extracted.
	 */
	function get_latest_rev( $site )
	{
		// Initialize caching
		$cache = DiskCache::getInstance();

		// Have we cached it?
		if ( isset( $cache->svn_repo ) )
			$raw = $cache->svn_repo;
		else
		{
			$raw = file_get_contents( $site );
			$cache->svn_repo = $raw;
		}

		$regex = "/(Revision)(\\s+)(\\d+)(:)/is";
		preg_match_all( $regex, $raw, $match );
		$revision = $match[3][0];

		return $revision;
	}

	/**
	 * write_definition()
	 *
	 * @abstract Writes to the list of keywords and their definitions.
	 *
	 * @param string $line The line to write to the file.
	 * @param array $commandarray The array of commands and their respective help entries.
	 * @return bool $successs Whether the write was successful or not.
	 */
	function write_definition( $line, $commandarray )
	{
		if ( isset( $line ) && $line != "list" ) {
			if ( $line[0] != " " ) {
				$linearray = explode( " ", $line );
				// Does there exist a definition?
				if ( $linearray[1] ) {
					if ( !array_key_exists( $linearray[0], $commandarray ) ) {
						$line = "\n" . $line;
						// Appends the new line only if it meets the following: existant, not blankspace and unique
						file_put_contents( DEFINITION_PATH, $line, FILE_APPEND );
						$success = 1;
						debug_message( "\"" . $line . "\" was written to the list of definitions!" );
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
	 * debug_message()
	 *
	 * @abstract Prints a debug-message with a time-stamp.
	 *
	 * @param string $message The message to be printed.
	 */
	function debug_message( $message )
	{
		if ( DEBUG ) {
			$line = "[" . @date( 'h:i:s' ) . "] " . $message . "\r\n";
			if ( GUI ) {
				echo "<br>" . $line;
			}
			else
			{
				echo $line;
			}
			$newpath = preg_replace( "/%date%/", @date( 'Ymd' ), LOG_PATH );
			file_put_contents( $newpath, $line, FILE_APPEND );
		}
	}

?>