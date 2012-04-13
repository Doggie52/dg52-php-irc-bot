<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Main class holding the main bot class and related functions
	 */

	/**
	 * IRCBot class.
	 */
	class IRCBot
	{
		/**
		 * Holds the raw data sent from the socket.
		 *
		 * @var string
		 */
		private $rawData;

		/**
		 * This is going to hold the current data object from the server.
		 *
		 * @var object
		 */
		private $data;

		/**
		 * This holds the plugin objects.
		 *
		 * @var array
		 */
		private $plugins;

		/**
		 * __construct()
		 *
		 * Constructs the bot by opening the server connection and CLI interface and loading all plugins.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct()
		{
			// Our TCP/IP connection
			global $socket;
			$socket = fsockopen( SERVER_IP, SERVER_PORT );

			// Our CLI interface
			global $cli;
			$cli = STDIN;

			// Set non-blocking mode on both sockets
			stream_set_blocking( $socket, 0 );
			stream_set_blocking( $cli, 0 );

			// Include class definitions
			include( "core/func.Functions.php" );
			include( "core/class.Data.php" );
			include( "core/class.Message.php" );
			include( "core/class.PluginHandler.php" );
			include( "core/cache/class.DiskCache.php" );

			// Set default cache path
			DiskCache::$cacheDir = DISK_CACHE_PATH;

			// Prune cache
			$cache = DiskCache::getInstance();
			$cache->prune();

			// Print header
			$this->print_header();

			// Stores list of administrators
			global $users;
			$users = $this->reload_users();

			// Replaces %date% with the date in the form yyyymmdd
			$newpath = preg_replace( "/%date%/", @date( 'Ymd' ), LOG_PATH );
			// Clears the logfile if LOG_APPEND is FALSE and if the file already exists
			if ( !LOG_APPEND && file_exists( $newpath ) ) {
				if ( unlink( $newpath ) )
					debug_message( "Log cleared!" );
			}

			// Load all plugins
			pluginHandler::load_plugins();
			// Trigger plugin load
			pluginHandler::trigger_hook( 'load' );
			// When the loop is broken, we have been greeted
			pluginHandler::trigger_hook( 'connect' );

			// Initializes the main bot workhorse
			$this->run();

			// Closes the socket
			fclose( $socket ) or die( 'Unable to close the socket!' );
		}

		/**
		 * run()
		 *
		 * @abstract This is the workhorse function, grabs the data from the server and displays on the browser if DEBUG_OUTPUT is true.
		 *
		 * @access private
		 * @return void
		 */
		private function run()
		{
			// Fetch the socket
			global $socket;
			// Fetch the CLI
			global $cli;

			while ( !feof( $socket ) )
			{
				/* Disabling due to Windows socket inconsistency
				if($cliinput = fgets($cli))
				{
					echo "CLI INPUT: ".$cliinput;
				}*/

				// Begin output buffering
				ob_start();

				// If there is something new in the socket (prevents over-use of resources)
				if ( $this->rawData = fgets( $socket ) ) {
					// If the debug output is turned on, spew out all data received from server
					if ( DEBUG_OUTPUT ) {
						debug_message( "[DEBUG] " . trim( $this->rawData ) );
					}

					// Parse the raw data
					$this->data = new Data( $this->rawData );

					// Ping?
					PluginHandler::$plugins['ServerActions']->pong( $this->data, $this->rawData );

					// If the message is a command
					if ( $this->data->type == Data::COMMAND )
						PluginHandler::run_command( $this->data->command, $this->data );
					else
					{
						if ( $this->data->origin == Data::CHANNEL )
							PluginHandler::trigger_hook( 'channel_message', $this->data );
						elseif ( $this->data->origin == Data::PM )
							PluginHandler::trigger_hook( 'private_message', $this->data );
					}
				}

				// End output buffering
				ob_end_flush();

				// Sleeps the bot to conserve CPU power
				if ( SLEEP_MSEC > 0 )
					usleep( SLEEP_MSEC );
			}
		}

		/**
		 * Prints the bot's header with useful information and some nice ASCII text.
		 *
		 * @access private
		 * @return void
		 */
		private function print_header()
		{
			$svnrev = get_latest_rev( "http://dg52-php-irc-bot.googlecode.com/svn/trunk/" );
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
	   Latest (online) revision: r" . $svnrev . "
	     (if you have an earlier revision than this, please update!)

	 Any issues, questions or feedback should be redirected
	 to the following URL.

	 http://code.google.com/p/dg52-php-irc-bot

				\n";
			if ( GUI )
				echo( nl2br( $info ) );
			else
				echo( $info );
		}

		/**
		 * (re)Loads the array of administrators
		 *
		 * @access private
		 * @return array $users The array of administrators
		 */
		private function reload_users()
		{
			// Turn the hostnames into lowercase (does not compromise security, as hostnames are unique anyway)
			if ( !( $userlist = strtolower( file_get_contents( USERS_PATH ) ) ) )
			{
				debug_message( "Something went wrong when loading the list of administrators." );
				return false;
			}

			// Split each line into separate entry in the returned array
			$users = explode( "\n", $userlist );

			debug_message( "The list of administrators was successfully loaded into the system!" );
			return $users;
		}

	}

?>