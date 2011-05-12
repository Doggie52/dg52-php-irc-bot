<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Main public executable wrapper
	 *
	 * TODO:
	 * 		- *ONGOING* Documentation for bot-commands
	 *		- Documentation for class
	 *		- *PARTIALLY DONE* Structure the class, perhaps outsource some features of main()
	 *		- Make use of usleep() to minimize CPU load
	 *		- *DONE* Log all actions in a log-file
	 *		- *DONE* Ability to reload speech array
	 *		- *DONE* Ability to add speech on-the-fly
	 *		- *DONE* Ability to format sent PMs
	 *		- Users and channel abilities
	 *			- *DONE* Ability to voice and de-voice (+v / -v)
	 *			- *DONE* Ability to set channel topic
	 *			- Ability to /whois a user and get response in a PM
	 *			- *DONE* Ability to invite a user
	 *			- Ability to add bot admin on-the-fly
	 *		- Bot abilities
	 *			- *DONE* Ability to use /me
	 *			- *DONE* Ability to refer to a username when responding
	 *			- Ability to use /notice
	 *			- *NOT WORKING* Ability to change nickname for the session
	 */	
	
	/**
	 * Pre-start checklist
	 */
	// Disable time-limit
	set_time_limit(0);
	// Let's hide those errors, shall we? No need for debug right now
	ini_set('display_errors', 'on');
	
	/**
	 * Main includes
	 */
	// Some configuration is needed
	include("cfg/config.php");
	// The main class definitions are also useful ;)
	include("core/class_main.php");
	
	/**
	 * Optional GUI
	 */
	// Are we in need of a GUI?
	if(GUI)
	{
		echo '
			<html>
				<head>
					<title>dG52 PHP IRC Bot - Console Window</title>
					<style type="text/css">
						#console {
							font-family: "Courier New";
							font-size: 10px;
						}
					</style>
				</head>
				<body>
					<div id="console">';
	}
	
	/**
	 * Main bot initialization!
	 */
	$bot = new IRCBot();
	
	// When the bot finally completes its endless cycle we want to clean up the GUI
	if(GUI)
	{
		echo'
					</div>
				</body>
			</html>';
	}
?>