<?php
	/**
	 * Server Actions
	 *
	 * Logs the bot onto the server, provides ability to pong server's pings and provides disconnect-functionality.
	 *
	 * @todo Implement error codes so we can see why the bot won't connect.
	 */

	class ServerActions extends PluginEngine
	{

		public $PLUGIN_NAME = "Server Actions";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Logs the bot onto the server, pongs on pings and provides disconnect functionality.";
		public $PLUGIN_VERSION = "1.0";

		/**
		 * Registers the bot on the server.
		 */
		public function register_bot()
		{
			$_cmd = new Message( 'USER', BOT_NICKNAME . ' douglasstridsberg.com ' . BOT_NICKNAME . ' :' . BOT_NAME );
			$_cmd = new Message( 'NICK', BOT_NICKNAME );

			// Temporarily tap into the socket
			global $socket;
			while ( !feof( $socket ) )
			{
				if ( ( $line = fgets( $socket ) ) ) {
					// If the debug output is turned on, spew out all data received from server
					if ( DEBUG_OUTPUT ) {
						debug_message( "DEBUG OUTPUT: " . trim( $line ) );
					}

					$_data = new Data( $line );

					// Check for pings sent before bot has connected
					if ( $this->pong( $_data, $line ) )
						continue;

					// If '433' is sent, we need to use the alternate bot nickname
					if ( $_data->replyCode == Data::ERR_NICKNAMEINUSE ) {
						$_cmd = new Message( 'NICK', BOT_NICKNAME_ALT );
						debug_message( "Bot was forced to use alternate nickname!" );
						continue;
					}

					// If '376' is sent, the bot has connected and received the last MOTD line
					// This has a potentital of not working with all IRCDs, in that case one should look for RPL_WELCOME
					if ( $_data->replyCode == Data::RPL_ENDOFMOTD ) {
						debug_message( "Bot was greeted." );
						break;
					}
				}
			}
		}

		public function quit( $data )
		{
			$_cmd = new Message( "QUIT", ":" . BOT_QUITMSG );

			debug_message( "Bot has disconnected and been turned off!" );
		}

		public function pong( $data, $rawdata )
		{
			if ( $data->type == Data::PING ) {
				// Explode raw data to get server
				$_temp_expl = explode( " ", $rawdata );
				// Plays ping-pong with the server to stay connected
				$pong = new Message( "PONG", $_temp_expl[1] );
				if ( !SUPPRESS_PING ) {
					debug_message( "PONG was sent." );
				}

				return true;
			}
		}

		public function __construct()
		{
			$this->register_action( 'load', array( 'ServerActions', 'register_bot' ) );

			$this->register_command( 'quit', array( 'ServerActions', 'quit' ) );
			$this->register_documentation( 'quit', array( 'auth_level' => 1,
				'access_type' => 'both',
				'documentation' => array( "*Usage:| !quit / !q",
					"Quits the server." )
			) );
		}
	}

?>