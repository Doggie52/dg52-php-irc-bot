<?php
/**
 * Print Info
 * 
 * Prints information about the bot and the server it is running on to users
 */

	class PrintInfo extends aPlugin
	{
		public $PLUGIN_NAME = "Print Info";
		public $PLUGIN_ID = "PrintInfo";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Prints information about the bot and the server it is running on to users.";
		public $PLUGIN_VERSION = "1.0";
		
		public $startTime;
		
		function onLoad()
		{
			// Declare the starttime
			$this->startTime = time();
			debug_message("Timestamp was set to ".$this->startTime);
		}
		
		function onConnect()
		{
		}
		
		function onDisconnect()
		{
		}
		
		/**
		 * Prints general information about the bot along with uptime to a user.
		 * 
		 * @access public
		 * @param string $username The username the info should be sent to
		 * @param string $starttime The start-time of the script in UNIX-timestamp format
		 * @return void
		 */
		function sendInfo($username, $starttime)
		{
			$this->display("dG52's PHP IRC Bot r".get_latest_rev("http://dg52-php-irc-bot.googlecode.com/svn/trunk/"), $username);
			// For UNIX-based systems, model and load can be $this->displayed
			if(PHP_OS != "WINNT" && PHP_OS != "WIN32")
			{
				$this->display("  Server OS: ".PHP_OS." (".php_uname().")", $username);
				$hwmodel = substr(@exec('sysctl hw.model'), 9);
				$this->display("  Server CPU model: ".$hwmodel, $username);
				$uptime = @exec('uptime');
				if($c = preg_match_all('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/', $uptime, $matches) > 0)
				{
					$this->display("  Average server load (past 1 minute): ".$matches[1][0], $username);
					$this->display("  Average server load (past 5 minutes): ".$matches[2][0], $username);
					$this->display("  Average server load (past 15 minutes): ".$matches[3][0], $username);
				}
			}
			else
			{
				$this->display("  Server OS: ".strtolower(PHP_OS), $username);
			}
			$currtime = time();
			$seconds = $currtime - $starttime;
			$minutes = floor($seconds / 60);
			$hours = floor($seconds / 3600);
			$seconds = $seconds;
			$this->display("  Bot uptime: ".$hours." hours ".$minutes." minutes ".$seconds." seconds.", $username);
			debug_message("Info was sent to ".$username."!");
		}
		
		function onCommand($command, $type, $from, $channel, $authLevel)
		{
			$command = explode(" ", $command);
			if(strtolower($command[0]) == "info")
			{
				$this->sendInfo($from, $this->startTime);
			}
		}
	}