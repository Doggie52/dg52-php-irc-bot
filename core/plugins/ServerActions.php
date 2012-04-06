<?php
/**
 * Server Actions
 * 
 * Logs the bot onto the server and provides disconnect-functionality.
 *
 * @todo Implement error codes so we can see why the bot won't connect.
 */

	class ServerActions extends PluginEngine
	{
		
		public $PLUGIN_NAME = "Server Actions";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Logs the bot onto the server and provides disconnect functionality.";
		public $PLUGIN_VERSION = "1.0";
	
		/**
		 * Registers the bot on the server.
		 */
		public function register_bot()
		{
			$_cmd = new Message('USER', BOT_NICKNAME.' douglasstridsberg.com '.BOT_NICKNAME.' :'.BOT_NAME);
			$_cmd = new Message('NICK', BOT_NICKNAME);

			// Temporarily tap into the socket
			global $socket;
			while(!feof($socket))
			{
				if(($line = fgets($socket)))
				{
					// If the debug output is turned on, spew out all data received from server
					if(DEBUG_OUTPUT)
					{
						debug_message("DEBUG OUTPUT: ".trim($line));
					}
					
					// If '376' is sent, the bot has connected and received the last MOTD line
					// This has a potentital of not working with all IRCDs, in that case one should look for RPL_WELCOME
					if(strpos($line, ' '.Data::RPL_ENDOFMOTD.' ') !== false)
					{
						debug_message("Bot was greeted.");
						break;
					}
				}
			}
		}
	
		public function quit($data)
		{
			if($data->authLevel != 1)
				return;
			
			if($_cmd = new Message("QUIT", ":".BOT_QUITMSG))
			{
				debug_message("Bot has disconnected and been turned off!");
			}
		}

		public function __construct()
		{
			$this->register_action('load', array('ServerActions', 'register_bot'));

			$this->register_command('quit', array('ServerActions', 'quit'));
			$this->register_documentation('quit', array('auth_level' => 1,
														'access_type' => 'both',
														'documentation' => array("*Usage:| !quit / !q",
																				"Quits the server.")
														));
		}
	}
?>