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
		public $plugins = array();
		
		/**
		 * Loads all plugins and appends them to array
		 */
		function __construct()
		{
			include("class_plugin.php");
			foreach(glob("core/plugins/*.php") as $pluginName)
			{
				include_once($pluginName);
				$pluginName = basename($pluginName, ".php");
				$this->plugins[$pluginName] = new $pluginName;
			}
		}
		
		/**
		 * Triggers an event notification based on what is in the $event array
		 * 
		 * @access public
		 * @param string $type Type of event (load, connect, disconnect, message, command, clicommand)
		 * @param string $data (opt.) Incoming message or command together with any parameters (i.e. the whole string)
		 * @param string $dataType (opt.) type of message or command (PRIVATE, CHANNEL)
		 * @param string $from (opt.) sender of message or command
		 * @param string $channel (opt.) channel in which message or command was sent
		 * @param string $authLevel (opt.) whether the sender is an administrator or not (1, 0)
		 * @return void
		 */
		function triggerEvent($type, $data = null, $dataType = null, $from = null, $channel = null, $authLevel = null)
		{
			switch($type)
			{
				case "load":
					foreach($this->plugins as $plugin)
					{
							$plugin->onLoad();
					}
					break;
				case "connect":
					foreach($this->plugins as $plugin)
					{
							$plugin->onConnect();
					}
					break;
				case "disconnect":
					foreach($this->plugins as $plugin)
					{
							$plugin->onDisconnect();
					}
					break;
				case "command":
					foreach($this->plugins as $plugin)
					{
							$plugin->onCommand($data, $dataType, $from, $channel, $authLevel);
					}
					break;
			}
		}
		
	}
?>