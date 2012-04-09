<?php
	/**
	 * Dictionary plugin
	 *
	 * Allows users to store and display custom definitions of words within the bot
	 */

	class Dictionary extends PluginEngine
	{
		public $PLUGIN_NAME = "Dictionary";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Allows bot to store and display a dictionary of custom definitions.";
		public $PLUGIN_VERSION = "1.0";

		// Documentation for future
		/*
		"add"		=> array("*Usage:* !add <keyword> <definition>",
						"Defines <keyword> as <definition> in the bots keyword repository."),
		"define"	=> array("*Usage:* !define <query>/list",
						"Looks up <query> in the bot's list of keywords and returns the definition.",
						"If 'list' is used, a list of the defined keywords is sent to the user."),
		*/
	}

?>