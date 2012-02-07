<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Arrays of speech available to the bot
	 */
	
	/**
	 * Documentation for authenticated users' PM commands
	 */
	$response['commands'][1]['pm'] = array(
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
						"Hash-sign in <channel> as well as <username> can be omitted.",
						"If the latter is omitted, the sender of the PM is assumed to be <username>."),
		"deop"		=> array(format_text("bold", "Usage:")." !deop <channel> <username>",
						"Removes <username>'s operator status in <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted.",
						"If the latter is omitted, the sender of the PM is assumed to be <username>."),
		"voice"		=> array(format_text("bold", "Usage:")." !voice <channel> <username>",
						"Voice's <username> in <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted.",
						"If the latter is omitted, the sender of the PM is assumed to be <username>."),
		"devoice"	=> array(format_text("bold", "Usage:")." !devoice <channel> <username>",
						"Removes <username> voice status in <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted.",
						"If the latter is omitted, the sender of the PM is assumed to be <username>."),
		"invite"	=> array(format_text("bold", "Usage:")." !invite / !i <username> <channel>",
						"Invites <username> to <channel>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted."),
		"info"		=> array(format_text("bold", "Usage:")." !info",
						"Prints information about the bot such as uptime and server hardware (if running on a UNIX system)."),
		"add"		=> array(format_text("bold", "Usage:")." !add <keyword> <definition>",
						"Defines <keyword> as <definition> in the bots keyword repository."),
		"topic"		=> array(format_text("bold", "Usage:")." !topic <channel> <topic>",
						"Sets the topic of <channel> to <topic>.",
						"Will only take effect if bot has sufficient privileges.",
						"Hash-sign can be omitted."),
	);
	/**
	 * Documentation for authenticated users' channel commands
	 */
	$response['commands'][1]['channel'] = array(
		"me"		=> array(format_text("bold", "Usage:")." !me <text>",
						"Makes the bot emote <text>."),
	);
	
	/**
	 * Documentation for non-authenticated users' PM commands
	 */
	$response['commands'][0]['pm'] = array(
	
	);
	/**
	 * Documentation for non-authenticated users' channel commands
	 */
	$response['commands'][0]['channel'] = array(
		"google"	=> array(format_text("bold", "Usage:")." !google <query>",
						"Queries Google search for <query> and returns the results."),
		"youtube"	=> array(format_text("bold", "Usage:")." !youtube <query>",
						"Queries YouTube search for <query> and returns the results."),
		"define"	=> array(format_text("bold", "Usage:")." !define <query>/list",
						"Looks up <query> in the bot's list of keywords and returns the definition.",
						"If 'list' is used, a list of the defined keywords is sent to the user."),
		"thetime"	=> array(format_text("bold", "Usage:")." !thetime",
						"Displays the current server time and the respective timezone."),
	);

?>