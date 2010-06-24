<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 */
	 
	/**
	 * Server configuration
	 */
	define("SERVER_IP", 		"irc.emulationnetwork.com");	// The IP of the server the bot will connect to
	define("SERVER_PORT", 		'6667');						// The port of the above server
	
	/**
	 * Bot configuration
	 */
	define("BOT_NICKNAME", 		"Quocom");						// The nickname the bot should use. No spaces allowed
	define("BOT_NAME", 			"Douglas's Bot");				// The "real" name of the bot
	define("BOT_PASSWORD", 		"pass");						// The password the bot should use when connecting to the server
	define("BOT_QUITMSG", 		"i am real pro");				// The message the bot will transmit when disconnecting from the server
	define("BOT_CHANNELS", 		"#doggie52 #test");				// Array of channels to join on connect. Values must be separated by a space
	
	/**
	 * Output configuration - whatever is shown will be logged as well
	 */
	define("GUI", 				FALSE); 						// Are you using a browser or not?
	define("DEBUG", 			TRUE);							// Do you wish to receive debug messages? If not, no logs will be written either
	define("DEBUG_OUTPUT", 		FALSE);							// Do you wish to receive the full server output? This will not be logged
	define("LOG_PATH", 			"log_%date%.txt");				// Path to the log-file relative to index.php. %date% results in "20100623"
	
	/**
	 * Miscellaneous paths
	 */
	define("DEFINITION_PATH", 	"encyclopedia.inc");			// The path to the definitions the bot uses relative to index.php
	define("USERS_PATH", 		"users.inc");					// The path to the definitions the bot uses relative to index.php

?>