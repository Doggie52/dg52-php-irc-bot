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
		public function onLoad()
		{
			send_data('USER', BOT_NICKNAME.' douglasstridsberg.com '.BOT_NICKNAME.' :'.BOT_NAME);
			send_data('NICK', BOT_NICKNAME);
			// Temporarily tap into the socket
			global $socket;
			while(!feof($socket))
			{
				// If "MOTD" is found the bot has been fully connected. Break the loop
				if(fgets($socket) && strpos(fgets($socket), "MOTD"))
				{
					debug_message("Bot was greeted.");
					break;
				}
			}
		}
	
		public function quit()
		{
			if(send_data("QUIT", ":".BOT_QUITMSG))
			{
				debug_message("Bot has disconnected and been turned off!");
			}
		}
	}
?>