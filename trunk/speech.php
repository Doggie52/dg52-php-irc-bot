<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 */

	$response['mention'] = array(
		"Oh! Someone mentioned my name!",
		"Woah! I am getting attention!",
		"Yes, that's me - what do you want :) ?",
		"Someone said my name!",
		"Cool, I am being mentioned!",
		BOT_NICKNAME." is my name, yes indeed...",
		"I am here, what do you want!"
	);
	
	$response['commands']['pm'] = array(
	"say"		=> array("Usage: !say <channel/username> <message>", "Posts <message> in <channel> or sends <message> as a PM to <username>."),
	"join"		=> array("Usage: !join / !j <channel>", "Joins <channel>. Hash-sign can be omitted."),
	"part"		=> array("Usage: !part / !p <channel>", "Parts <channel>. Hash-sign can be omitted."),
	"quit"		=> array("Usage: !quit / !q", "Quits the server."),
	"reload"	=> array("Usage: !reload", "Reloads the speech-array.", "Will mostly be done automatically, unless you edit the files manually you should not need to run this."),
	"op"		=> array("Usage: !op <channel> <username>", "Gives <username> operator status in <channel>.", "Will only take effect if bot has sufficient privileges")	
	);

?>