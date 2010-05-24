<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 */

// Disables script time-out and enable errors
set_time_limit(0);
ini_set('display_errors', 'on');

// Include configuration
include("config.php");

// Include main class
include("class_main.php");

if(GUI)
{
?>
<title>dG52 PHP IRC Bot - Console Window</title>
<style type="text/css">
	#console {
		font-family: "Courier New";
		font-size: 10px;
	}
</style>
<div id="console">
<?php
}

	$bot = new IRCBot();

if(GUI)
{
?>
</div>
<?php
}
?>