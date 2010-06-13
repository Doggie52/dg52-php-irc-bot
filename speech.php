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
		"say"		=> array(format_text("bold", "Usage:")." !say / !s <channel/username> <message>",
						"Posts <message> in <channel> or sends <message> as a PM to <username>.",
						"Hash-sign can be omitted."),
		"join"		=> array(format_text("bold", "Usage:")." !join / !j <channel>",
						"Joins <channel>.",
						"Hash-sign can be omitted."),
		"part"		=> array(format_text("bold", "Usage:")." !part / !p <channel>",
						"Parts <channel>.",
						"Hash-sign can be omitted."),
		"quit"		=> array(format_text("bold", "Usage:")." !quit / !q",
						"Quits the server."),
		"reload"	=> array(format_text("bold", "Usage:")." !reload",
						"Reloads the speech-array.",
						"Will mostly be done automatically, unless you edit the files manually you should not need to run this."),
		"op"		=> array(format_text("bold", "Usage:")." !op <channel> <username>",
						"Gives <username> operator status in <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted."),
		"deop"		=> array(format_text("bold", "Usage:")." !deop <channel> <username>",
						"Removes <username>'s operator status in <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted."),
		"voice"		=> array(format_text("bold", "Usage:")." !voice <channel> <username>",
						"Voice's <username> in <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted."),
		"devoice"	=> array(format_text("bold", "Usage:")." !devoice <channel> <username>",
						"Removes <username> voice status in <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted."),
		"invite"	=> array(format_text("bold", "Usage:")." !invite / !i <username> <channel>",
						"Invites <username> to <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted."),
		"info"		=> array(format_text("bold", "Usage:")." !info",
						"Prints information about the bot such as uptime."),
		"add"		=> array(format_text("bold", "Usage:")." !add <keyword> <definition>",
						"Defines <keyword> as <definition> in the bots keyword repository."),
		"topic"		=> array(format_text("bold", "Usage:")." !topic <channel> <topic>",
						"Sets the topic of <channel> to <topic>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted."),
	);

?>