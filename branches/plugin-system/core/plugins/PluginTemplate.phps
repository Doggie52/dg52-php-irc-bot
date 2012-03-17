<?php
/**
 * TemplatePlugin plugin
 * 
 * <describe your plugin here>
 */

	class TemplatePlugin extends PluginEngine
	{
		/**
		 * Mandatory plugin properties
		 */
		public $PLUGIN_NAME = "<name your plugin>";
		public $PLUGIN_AUTHOR = "<author name>";
		public $PLUGIN_DESCRIPTION = "<describe plugin behavior>";
		public $PLUGIN_VERSION = "1.0";

		/**
		 * Properties
		 */
		

		/** 
		 * Constructor
		 */
		public function __construct()
		{
			// Register function to a hook
			$this->register_action('hook_name', array('TemplatePlugin', 'function_name'));

			// Register function to a command
			$this->register_command('command_name', array('TemplatePlugin', 'other_function_name'));
		}

		public function function_name($data)
		{
			
		}

		public function other_function_name($data)
		{
			
		}
				
	}
?>