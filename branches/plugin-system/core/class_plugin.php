<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Plugin functionality, API between bot and plugin.
	 * Much inspired by http://www.w3style.co.uk/writing-a-pluggable-system-in-php.
	 */

	/**
	 * Plugin interface/API
	 */
	abstract class PluginEngine
	{
	
		/**
		 * Variables
		 */
		public $PLUGIN_NAME, $PLUGIN_AUTHOR, $PLUGIN_DESCRIPTION, $PLUGIN_VERSION;

		/**
		 * Registers a callback to a hook
		 *
		 * @access protected
		 * @param string $hook The hook to register the callback to
		 * @param array $callback [0]: name of the plugin's class, [1]: name of the function (callback) to register to the hook
		 */
		protected function register_action($hook, $callback)
		{
			// Does the hook exist?
			if(!isset(PluginHandler::$hooks[$hook]))
			{
				debug_message("[PLUGIN] ".$callback[0].": Hook \"".$hook."\" does not exist!");
				return;
			}
			
			// Add callback to hook array
			PluginHandler::$hooks[$hook][] = $callback;
		}

		/**
		 * Special case of registering a callback to a hook, when the plugin registers a command
		 *
		 * @access protected
		 * @param string $command The name of the command to register
		 * @param array $callback [0]: name of the plugin's class, [1]: name of the function (callback) to register to the hook
		 * @param string $documentation The usage documentation assigned to the command
		 */
		protected function register_command($command, $callback, $documentation = null)
		{
			// Does the command already exist?
			if(isset(PluginHandler::$commands[$command]))
			{
				debug_message("[PLUGIN] ".$callback[0].": Command \"".$command."\" already exists!");
				return;
			}
			
			// Add callback to command array
			PluginHandler::$commands[$command] = $callback;
		}
		
		/**
		 * Displays a message to either the channel or a client
		 * 
		 * @access protected
		 * @param string $message The message to display
		 * @param string $to The client or channel to send the message to
		 */
		protected function display($message, $to)
		{
			$this->send_data("PRIVMSG", $message, $to);
		}

		/**
		 * Sends data to the server. Important that basic structure of sent message is kept the same, otherwise it will fail.
		 * 
		 * @access protected
		 * @param string $cmd The command you wish to send
		 * @param string $msg The parameters you wish to pass to the command (default: null)
		 * @param string $rcvr The receiver of the message (default: null)
		 * @return boolean Whether the data was sent successfully
		 */
		protected function send_data($cmd, $msg = null, $rcvr = null)
		{
			send_data($cmd, $msg, $rcvr);
		}
		
	}

?>