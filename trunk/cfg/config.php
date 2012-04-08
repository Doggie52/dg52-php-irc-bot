<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Main bot configuration file
	 */
	 
	/**
	 * Server configuration
	 */
	define("SERVER_IP", 		"irc.freenode.net");			// The IP of the server the bot will connect to.
	define("SERVER_PORT", 		'6667');						// The port of the above server.
	
	/**
	 * Bot configuration
	 */
	define("BOT_NICKNAME", 		"Quocom");						// The nickname the bot should use. No spaces allowed.
	define("BOT_NAME", 			"dG52 PHP IRC Bot");			// The "real" name of the bot.
	define("BOT_PASSWORD", 		"");							// The password the bot should use when connecting to the server.
	define("BOT_QUITMSG", 		"dG52 PHP IRC Bot");			// The message the bot will transmit when disconnecting from the server.
	define("BOT_CHANNELS", 		"#doggie52");					// Array of channels to join on connect. Values must be separated by a space.
	define("COMMAND_PREFIX",	"!");							// The prefix that identifies a command.

	/**
	 * Caching settings
	 */
	define("DISK_CACHE_PATH",	"cache");						// The path, relative to the root directory, of where to store cache files.
	
	/**
	 * Output configuration - whatever is shown will be logged as well
	 */
	define("GUI", 				false); 						// Are you using a browser or not?
	define("DEBUG", 			true);							// Do you wish to receive debug messages? If not, no logs will be written either.
	define("DEBUG_OUTPUT", 		false);							// Do you wish to receive the full server output?
	define("LOG_PATH", 			"log_%date%.txt");				// Path to the log-file relative to index.php. %date% results in "20100623".
	define("LOG_APPEND",		false);							// Either append or overwrite the log.
	define("SUPPRESS_PING",		true);							// Suppress the PING/PONG messages from appearing in the log.

	/**
	 * Performance settings
	 */
	define("SLEEP_MSEC",		100);							// If not set to false or 0, will tell the bot to sleep a certain number of msec after every iteration, can improve performance.
	
	/**
	 * Miscellaneous paths
	 */
	define("USERS_PATH", 		"cfg/users.inc");				// The path to the list of administrators the bot uses (relative to index.php).

?>