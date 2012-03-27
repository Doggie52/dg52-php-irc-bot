<?php
/**
 * HelloWorld plugin
 * 
 * Sample plugin to showcase simple "Hello World!"-functionality.
 */

	class HelloWorld extends PluginEngine
	{
		public $PLUGIN_NAME = "Hello World";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Outputs hello world at different times.";
		public $PLUGIN_VERSION = "1.0";

		private $matches = array(
							'hello',
							'hi',
							'hey',
							'howdy',
							'yo'
			);
		
		public function __construct()
		{
			// Register our function to both private and channel messages
			$this->register_action('channel_message', array('HelloWorld', 'hello'));
			$this->register_action('private_message', array('HelloWorld', 'hello'));
		}

		public function hello($data)
		{
			// Since what we are looking for is not a command, we need to explode the full line
			$fullLine = explode(" ", $data->fullLine);

			// If the first word matches any of our matches
			if(in_array(strtolower($fullLine[0]), $this->matches))
			{
				if($data->origin == Data::PM)
				{
					$_msg = new Message("PRIVMSG", "Hello world! You just sent this via PM.", $data->sender);
				}
				elseif($data->origin == Data::CHANNEL)
				{
					$_msg = new Message("PRIVMSG", "Hello world! You just sent me this via a channel.", $data->sender);
				}

				// Send test output
				$_msg = new Message("PRIVMSG", "*Bold|. +Italic|. __Underscore|. ", $data->sender);
				$_msg = new Message("PRIVMSG", "*Bold|. *+BoldItalic|. *__BoldUnderscore|. ", $data->sender);
				$_msg = new Message("PRIVMSG", "+Italic|. +__ItalicUnderscore|. ", $data->sender);
				$_msg = new Message("PRIVMSG", "__Underscore|. *+__BoldItalicUnderscore|.", $data->sender);
			}
		}		
	}
?>