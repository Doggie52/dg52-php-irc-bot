<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Handles plugins
	 */

	class PluginHandler
	{
		/**
		 * A static list of plugins, available to access by any plugin without having to instantiate object
		 */
		static public $plugins = array();
		
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
				PluginHandler::$plugins[$pluginName] = new $pluginName;
			}
		}
		
		/**
		 * Triggers an event notification based on parameters
		 * 
		 * @access public
		 * @param string $type Type of event (load, connect, disconnect, message, command, clicommand)
		 * @param array $_DATA (opt.) Array of incoming data
		 * 				$_DATA['fullCommand'] The full command line without the beginning prefix
		 * 				$_DATA['messageType'] Type of message or command ("PRIVATE" or "CHANNEL")
		 * 				$_DATA['sender'] Sender of message or command
		 * 				$_DATA['receiver'] Either the channel where the message was sent or the bot's nickname
		 * 				$_DATA['authLevel'] Whether the sender is an administrator or not (1 or 0)
		 * @return void
		 */
		static public function triggerEvent($type, $_DATA = array())
		{
			switch($type)
			{
				case "load":
					foreach(PluginHandler::$plugins as $plugin)
					{
						if(method_exists($plugin, "onLoad"))
						{
							$plugin->onLoad();
						}
					}
					break;
				case "connect":
					foreach(PluginHandler::$plugins as $plugin)
					{
						if(method_exists($plugin, "onConnect"))
						{
							$plugin->onConnect();
						}		
					}
					break;
				case "disconnect":
					foreach(PluginHandler::$plugins as $plugin)
					{
						if(method_exists($plugin, "onDisconnect"))
						{
							$plugin->onDisconnect();
						}
					}
					break;
				case "command":
					foreach(PluginHandler::$plugins as $plugin)
					{
						if(method_exists($plugin, "onCommand"))
						{
							$plugin->onCommand($_DATA);
						}
					}
					break;
			}
		}
		
	}
?>