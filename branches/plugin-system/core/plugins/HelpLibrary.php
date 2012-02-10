<?php
/**
 * HelpLibrary plugin
 * 
 * Displays help about available functions to different users.
 * The plugin is unfinished for now, the plan is to build a system that shows users the commands available to them. In the end, this plugin will replace "speech.php"
 */

	class HelpLibrary extends PluginEngine
	{
		public $PLUGIN_NAME = "Help Library";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Displays help about functions.";
		public $PLUGIN_VERSION = "1.0";

		/**
		 * A local variable to hold a copy of the code documentation
		 */
		private $commandDocumentation;

		public function __construct()
		{
			$this->register_action('load', array('HelpLibrary', 'init'));
		}
		
		/**
		 * Copies the available commands and their documentation to the plugin
		 */
		public function init()
		{
			$this->commandDocumentation = PluginHandler::$commands;
		}
	}
?>