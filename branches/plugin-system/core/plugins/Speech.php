<?php
/**
 * Speech plugin
 * 
 * Allows the bot to communicate with users in different ways.
 * Unfinished for now, plan is to allow following functionality:
 * - say
 * - /me
 * - random responses to BOT_NICKNAME
 * -
 */

	class Speech extends PluginEngine
	{
		public $PLUGIN_NAME = "Speech";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Allows bot to communicate with users.";
		public $PLUGIN_VERSION = "1.0";


		/**
		 * List of random responses the bot can respond with.
		 */
		private $randomResponses = array(
				"Oh! Someone mentioned my name!",
				"Woah! I am getting attention!",
				"Yes, that's me - what do you want :) ?",
				"Someone said my name!",
				"Cool, I am being mentioned!",
				BOT_NICKNAME." is my name, yes indeed...",
				"I am here, what do you want!",
				"What can I do for you?",
				"How can I be of service?",
				BOT_NICKNAME." is my name, answering questions is my game.",
				"Hello %username%, how can I help?",
				"It's a beautiful day to be talking to you %username%, but let's get down to business.",
				"Greetings good Sir! (Or Madam!). Do you require my assistance?",
				"Have no fear, for I am here!",
				"Have no fear, for ".BOT_NICKNAME." is here!",
			);

		public function __construct()
		{
			
		}
		
		/**
		 * Allows a user to make the bot say something
		 */
		public function say($data)
		{
			
		}

		/**
		 * Allows a user to make the bot emote something (i.e. /me emote here)
		 */
		public function emote($data)
		{
			
		}

		/**
		 * Issues random responses when BOT_NICKNAME is mentioned in channel chat
		 */
		public function random_respond($data)
		{
			
		}

	}