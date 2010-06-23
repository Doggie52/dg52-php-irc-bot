<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 */

	// Server configuration
	define("SERVER_IP", "irc.emulationnetwork.com");
	define("SERVER_PORT", '6667');
	
	// Bot configuration
	define("BOT_NICKNAME", "Quocom");
	define("BOT_NAME", "Douglas's Bot");
	define("BOT_PASSWORD", "pass");
	define("BOT_QUITMSG", "i am real pro");
	
	// Debug and log configuration - whatever is shown is logged as well
	define("GUI", FALSE); 					// Are you using a browser or not?
	define("DEBUG", TRUE);					// Do you wish to receive debug messages? If not, no logs will be written either
	define("DEBUG_OUTPUT", FALSE);			// Do you wish to receive the full server output? This will not be logged
	define("LOG_PATH", "log_%date%.txt");	// Path to the log-file relative to index.php. %date% results in "20100623"


?>