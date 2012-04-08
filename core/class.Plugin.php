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
		 * Allows plugins to register documentation for their commands with this library
		 *
		 * @access protected
		 * @param string $command The name of the command to which to register documentation
		 * @param mixed $documentation The string (one line) or array (multi-line, one line per entry) of documentation available for the command
		 */
		static function register_documentation($command, $documentation)
		{
			// Checks for empty arguments
			if(empty($command) || empty($documentation))
			{
				debug_message("Both arguments need to be filled in.");

				return;
			}

			// Makes sure $ocumentation is the correct structure
			if(!isset($documentation['auth_level']) || !isset($documentation['access_type']) || empty($documentation['documentation']))
			{
				debug_message("The correct documentation structure is needed.");

				return;
			}

			// Checks for already existing documentation
			if(!empty(PluginHandler::$documentation[$command]))
			{
				debug_message("Documentation for the command \"".$command."\" already exists.");

				return;
			}

			PluginHandler::$documentation[$command] = $documentation;
		}

		/**
		 * Triggers a debug message that can be tied back to the plugin
		 *
		 * @todo Send name of plugin class with the message
		 *
		 * @access protected
		 * @param string $msg The debug message to be sent
		 */
		protected function debug_message($msg)
		{
			debug_message("[PLUGIN] ".$msg);
		}

	}

?>