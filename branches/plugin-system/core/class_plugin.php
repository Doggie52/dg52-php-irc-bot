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
		 * Variables
		 */
		public $PLUGIN_NAME, $PLUGIN_ID, $PLUGIN_AUTHOR, $PLUGIN_DESCRIPTION, $PLUGIN_VERSION;
		
		/**
		 * Displays a message to either the channel or a client
		 * 
		 * @access public
		 * @param string $message The message to display
		 * @param string $to The client or channel to send the message to
		 */
		public function display($message, $to)
		{
			send_data("PRIVMSG", $message, $to);
		}
		
	}

?>