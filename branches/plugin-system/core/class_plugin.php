<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Plugin functionality
	 * Much inspired by http://www.w3style.co.uk/writing-a-pluggable-system-in-php
	 */

	/**
	 * Plugin interface
	 */
	abstract class aPlugin
	{
		/**
		 * Hooks/event-handlers
		 */
		abstract function onLoad();
		abstract function onConnect();
		abstract function onDisconnect();
		// function onMessage($message, $type, $from);
		
		/**
		 * Hook on a command
		 * 
		 * @param string $command The command sent (without the !)
		 * @param string $type Type of command sent (CHANNEL, PRIVMSG)
		 * @param string $from The sender of the command
		 * @param string $channel Channel in which message or command was sent
		 * @param string $authLevel Whether the sender is administrator
		 */
		abstract function onCommand($command, $type, $from, $channel, $authLevel);	// only triggered by !-prefixed messages
		
		/**
		 * Displays a message to either the channel or a client
		 * 
		 * @param string $message The message to display
		 * @param string $to The client or channel to send the message to
		 */
		function display($message, $to)
		{
			send_data("PRIVMSG", $message, $to);
		}
		
		/**
		 * Variables
		 */
		public $PLUGIN_NAME, $PLUGIN_ID, $PLUGIN_AUTHOR, $PLUGIN_DESCRIPTION, $PLUGIN_VERSION;
	}

?>