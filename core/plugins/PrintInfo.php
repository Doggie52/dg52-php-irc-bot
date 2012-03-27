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
		
		public function init()
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
		private function send_info($username, $starttime)
		{
			$_msg = new Message("PRIVMSG", "*dG52's PHP IRC Bot| r".get_latest_rev("http://dg52-php-irc-bot.googlecode.com/svn/trunk/"), $username);
			
			// For UNIX-based systems, model and load can be fetched
			if(PHP_OS != "WINNT" && PHP_OS != "WIN32")
			{
				// Prepare caching
				$cache = DiskCache::getInstance();

				// If there is no cache yet
				if(!isset($cache->system_info))
				{
					$system['OS'] = PHP_OS." (".php_uname().")";
					$system['CPU'] = substr(@exec('sysctl hw.model'), 9);

					$cache->system_info = $system;
				}
				else
				{
					$system = $cache->system_info;
				}

				$_msg = new Message("PRIVMSG", "  +Server OS:| ".$system['OS'], $username);
				$_msg = new Message("PRIVMSG", "  +Server CPU model:| ".$system['CPU'], $username);
				$uptime = @exec('uptime');
				if($c = preg_match_all('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/', $uptime, $matches) > 0)
				{
					$_msg = new Message("PRIVMSG", "  +Average server load (past 1 minute):| ".$matches[1][0], $username);
					$_msg = new Message("PRIVMSG", "  +Average server load (past 5 minutes):| ".$matches[2][0], $username);
					$_msg = new Message("PRIVMSG", "  +Average server load (past 15 minutes):| ".$matches[3][0], $username);
				}
			}
			else
			{
				$_msg = new Message("PRIVMSG", "  +Server OS:| ".strtolower(PHP_OS), $username);
			}

			$currtime = time();
			$seconds = $currtime - $starttime;
			$minutes = floor($seconds / 60);
			$hours = floor($seconds / 3600);
			$seconds = $seconds;

			$_msg = new Message("PRIVMSG", "  +Bot uptime:| ".$hours." hours ".$minutes." minutes ".$seconds." seconds.", $username);
			
			debug_message("Info was sent to ".$username."!");
		}
		
		public function command_info($data)
		{
			if($data->authLevel == 1)
				$this->send_info($data->sender, $this->startTime);
		}

		public function __construct()
		{
			$this->register_action('connect', array('PrintInfo', 'init'));

			$this->register_command('info', array('PrintInfo', 'command_info'));
			$this->register_documentation('info', array('auth_level' => 1,
														'access_type' => 'both',
														'documentation' => array("*Usage:| !info",
																				"Prints information about the bot such as uptime and server hardware (if running on a UNIX system).")
														));
		}
	}