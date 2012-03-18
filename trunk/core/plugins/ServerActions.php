<?php
/**
 * Server Actions
 * 
 * Logs the bot onto the server and provides disconnect-functionality.
 */

	class ServerActions extends PluginEngine
	{
		
		public $PLUGIN_NAME = "Server Actions";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Logs the bot onto the server.";
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
				// If "MOTD" is found the bot has been fully connected. Break the loop
				if(fgets($socket) && strpos(fgets($socket), "MOTD") !== FALSE)
				{
					debug_message("Bot was greeted.");
					break;
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