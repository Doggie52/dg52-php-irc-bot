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
	 * @TODO:
	 *		- Documentation for class
	 *		- Make use of usleep() to minimize CPU load
	 *		- Users and channel abilities
	 *			- Ability to /whois a user and get response in a PM
	 *			- Ability to add bot admin on-the-fly
	 *		- Bot abilities
	 *			- Ability to use /notice
	 *			- Ability to change nickname for the session
	 */

	/**
	 * Pre-start checklist
	 */
	// Disable time-limit
	set_time_limit( 0 );
	// Let's hide those errors, shall we? No need for debug right now
	ini_set( 'display_errors', 'on' );
	// Checking to see if bot has been configured properly
	if ( !file_exists( "core/config.php" ) )
		exit( debug_message( 'You must configure the bot before running it! Open cfg/config.dist.php for more information.' ) );

	/**
	 * Main includes
	 */
	require( "cfg/config.php" );
	include( "core/class.IRCBot.php" );

	/**
	 * Optional GUI
	 */
	// Are we in need of a GUI?
	if ( GUI ) {
		echo '
			<html>
				<head>
					<title>dG52 PHP IRC Bot - Console Window</title>
					<style type="text/css">
						#console {
							font-family: "Courier New", monospace;
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
	if ( GUI ) {
		echo'
					</div>
				</body>
			</html>';
	}
?>