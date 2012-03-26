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
	define("SERVER_IP", 		"irc.freenode.net");			// The IP of the server the bot will connect to
	define("SERVER_PORT", 		'6667');						// The port of the above server
	
	/**
	 * Bot configuration
	 */
	define("BOT_NICKNAME", 		"Quocom");						// The nickname the bot should use. No spaces allowed
	define("BOT_NAME", 			"Douglas's Bot");				// The "real" name of the bot
	define("BOT_PASSWORD", 		"pass");						// The password the bot should use when connecting to the server
	define("BOT_QUITMSG", 		"i am real pro");				// The message the bot will transmit when disconnecting from the server
	define("BOT_CHANNELS", 		"#doggie52");					// Array of channels to join on connect. Values must be separated by a space
	define("COMMAND_PREFIX",	"!");							// The prefix that identifies a command

	/**
	 * Caching settings
	 */
	define("DISK_CACHE_PATH",	"cache");						// The path, relative to the root directory, of where to store cache files
	
	/**
	 * Output configuration - whatever is shown will be logged as well
	 */
	define("GUI", 				FALSE); 						// Are you using a browser or not?
	define("DEBUG", 			TRUE);							// Do you wish to receive debug messages? If not, no logs will be written either
	define("DEBUG_OUTPUT", 		FALSE);							// Do you wish to receive the full server output?
	define("LOG_PATH", 			"log_%date%.txt");				// Path to the log-file relative to index.php. %date% results in "20100623"
	define("LOG_APPEND",		FALSE);							// Either append or overwrite the log
	define("SUPPRESS_PING",		TRUE);							// Suppress the PING/PONG messages from appearing in the log
	
	/**
	 * Miscellaneous paths
	 */
	define("USERS_PATH", 		"cfg/users.inc");				// The path to the list of administrators the bot uses (relative to index.php)
	define("DEFINITION_PATH", 	"extra/encyclopedia.inc");		// The path to the definitions the bot uses (relative to index.php)

?>