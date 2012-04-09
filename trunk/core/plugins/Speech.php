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
		private $randomResponses;

		/**
		 * Populates the random responses and register the plugin's commands and actions
		 */
		public function __construct()
		{
			$this->randomResponses = array(
				"Oh! Someone mentioned my name!",
				"Woah! I am getting attention!",
				"Yes, that's me - what do you want :) ?",
				"Someone said my name!",
				"Cool, I am being mentioned!",
				BOT_NICKNAME . " is my name, yes indeed...",
				"I am here, what do you want!",
				"What can I do for you?",
				"How can I be of service?",
				BOT_NICKNAME . " is my name, answering questions is my game.",
				"Hello %username%, how can I help?",
				"It's a beautiful day to be talking to you %username%, but let's get down to business.",
				"Greetings good Sir! (Or Madam!). Do you require my assistance?",
				"Have no fear, for I am here!",
				"Have no fear, for " . BOT_NICKNAME . " is here!",
			);

			$this->register_action('channel_message', array('Speech', 'random_respond'));

			$this->register_command('me', array('Speech', 'emote'));
			$this->register_documentation('me', array('auth_level' => 1,
				'access_type' => 'channel',
				'documentation' => array("*Usage:| !me <text>",
					"Makes the bot emote <text>.")
			));
		}

		/**
		 * Allows a user to make the bot say something
		 *
		 * @todo Actually find a use for this functionality
		 */
		public function say($data)
		{

		}

		/**
		 * Allows a user to make the bot emote something (i.e. /me emote here)
		 */
		public function emote($data)
		{
			// Only check channel
			if($data->origin == Data::CHANNEL) {
				$emote = substr($data->fullLine, 4);

				// If any emote was given
				if(strlen($emote) != 0) {
					// SOH character to indicate and end action
					$_msg = new Message("PRIVMSG", chr(1) . "ACTION " . $emote . chr(1), $data->receiver);
				}
			}
		}

		/**
		 * Issues random responses when BOT_NICKNAME is mentioned in channel chat
		 */
		public function random_respond($data)
		{
			// Only check channel
			if($data->origin == Data::CHANNEL) {
				// If the bot's nickname is found somewhere, send a random response
				if(stripos($data->fullLine, BOT_NICKNAME) !== FALSE) {
					$random_int = mt_rand(0, count($this->randomResponses) - 1);

					$phrase = str_replace('%username%', $data->sender, $this->randomResponses[$random_int]);

					$_msg = new Message("PRIVMSG", $phrase, $data->receiver);
				}
			}
		}

	}