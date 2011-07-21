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
		
		public function onLoad()
		{
		}
		
		public function onConnect()
		{
		}
		
		public function onDisconnect()
		{
		}
		
		public function onCommand($_DATA)
		{
			$command = explode(" ", $_DATA['fullCommand']);
			if(strtolower($command[0]) == "hello")
			{
				if($_DATA['messageType'] == "PRIVATE")
				{
					$this->display("Hello world! You just sent me a PM.", $_DATA['sender']);
				}
				elseif($_DATA['messageType'] == "CHANNEL")
				{
					$this->display("Hello world! You just sent me this via a channel.", $_DATA['sender']);
				}
			}
		}
		
	}
?>