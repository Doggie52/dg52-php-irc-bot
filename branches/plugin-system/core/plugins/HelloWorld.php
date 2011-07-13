<?php
/**
 * HelloWorld plugin
 * 
 * Outputs hello world at different times
 */

	class HelloWorld extends aPlugin
	{
		public $PLUGIN_NAME = "Hello World";
		public $PLUGIN_ID = "HelloWorld";
		public $PLUGIN_AUTHRO = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Outputs hello world at different times.";
		public $PLUGIN_VERSION = "1.0";
		
		function onLoad()
		{
		}
		
		function onConnect()
		{
		}
		
		function onDisconnect()
		{
		}
		
		function onCommand($command, $type, $from, $authLevel)
		{
			if($command == "hello")
			{
				if($type == "PRIVATE")
				{
					$this->display("Hello world! You just sent me a PM.", $from);
				}
				elseif($type == "CHANNEL")
				{
					$this->display("Hello world! You just sent me this via a channel.", $from);
				}
			}
		}
		
	}
?>