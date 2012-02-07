<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Handles plugins.
	 */

	class PluginHandler
	{
		/**
		 * A static list of plugin objects, available to access by any plugin without having to instantiate object
		 */
		static public $plugins = array();

		/**
		 * A static list of plugin hooks with an array each to stored the callbacks
		 */
		static public $hooks = array(
								'load' => array(),				// called when bot has loaded
								'connect' => array(),			// called when bot has successfully connected
								'disconnect' => array(),		// called when bot has disconnected
								'private_message' => array(),	// called when bot receives a private message
								'channel_message' => array(),	// called when the channel the bot is in receivs a message
			);
		
		/**
		 * A static list of the commands registered by all plugins
		 */
		static public $commands = array();
		
		private function __construct()
		{
			
		}
		
		/**
		 * Loads all plugins and appends them to array
		 */
		static public function loadPlugins()
		{
			include("class_plugin.php");
			foreach(glob("core/plugins/*.php") as $pluginName)
			{
				include_once($pluginName);
				// Get the plugin name without the .php
				$pluginName = basename($pluginName, ".php");
				self::$plugins[$pluginName] = new $pluginName;
			}
		}
		
		/**
		 * Triggers an event notification based on parameters
		 * 
		 * @access public
		 * @param string $hook Name of the hook to fire
		 * @param object $data (optional) The data object to pass to plugins
		 * @return void
		 */
		static public function triggerHook($hook, $data = null)
		{
			// Does the hook exist?
			if(!isset(self::$hooks[$hook]))
				return;
			
			// Fire the events
			foreach(self::$hooks[$hook] as $callback)
				call_user_func_array(array(self::$plugins[$callback[0]], $callback[1]), array($data));
		}
		
	}
?>