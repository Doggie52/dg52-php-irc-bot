<?php
/**
 * HelloWorld plugin
 * 
 * Sample plugin to showcase simple "Hello World!"-functionality
 */

	class HelloWorld extends aPlugin
	{
		public $PLUGIN_NAME = "Hello World";
		public $PLUGIN_ID = "HelloWorld";
		public $PLUGIN_AUTHOR = "Doggie52";
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
		
		function onCommand($command, $type, $from, $channel, $authLevel)
		{
			$command = explode(" ", $command);
			if(strtolower($command[0]) == "hello")
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