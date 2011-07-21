<?php
/**
 * Print Info
 * 
 * Prints information about the bot and the server it is running on to users.
 */

	class PrintInfo extends PluginEngine
	{
		public $PLUGIN_NAME = "Print Info";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Prints information about the bot and the server it is running on to users.";
		public $PLUGIN_VERSION = "1.0";
		
		private $startTime;
		
		public function onLoad()
		{
			// Declare the starttime
			$this->startTime = time();
			debug_message("Uptime timer started.");
		}
		
		/**
		 * Prints general information about the bot along with uptime to a user.
		 * 
		 * @access private
		 * @param string $username The username the info should be sent to
		 * @param string $starttime The start-time of the script in UNIX-timestamp format
		 * @return void
		 */
		private function sendInfo($username, $starttime)
		{
			$this->display("dG52's PHP IRC Bot r".getLatestRev("http://dg52-php-irc-bot.googlecode.com/svn/trunk/"), $username);
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
		
		public function onCommand($_DATA)
		{
			$command = explode(" ", $_DATA['fullCommand']);
			if(strtolower($command[0]) == "info")
			{
				$this->sendInfo($_DATA['sender'], $this->startTime);
			}
		}
	}