<?php
/**
 * TheTime plugin
 * 
 * Displays the current bot time
 */

	class TheTime extends PluginEngine
	{
		/**
		 * Mandatory plugin properties
		 */
		public $PLUGIN_NAME = "The Time";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Displays the bot's current time to a user.";
		public $PLUGIN_VERSION = "1.0";

		/**
		 * Properties
		 */
		

		/** 
		 * Constructor
		 */
		public function __construct()
		{
			$this->register_command('thetime', array('TheTime', 'the_time'));
		}

		public function the_time($data)
		{
			// Only check channel
			if($data->origin == Data::CHANNEL)
			{
				$date = date("H:ia T");
				$_msg = new Message("PRIVMSG", "The time is +".$date."+.", $data->receiver);
			}
		}
				
	}
?>