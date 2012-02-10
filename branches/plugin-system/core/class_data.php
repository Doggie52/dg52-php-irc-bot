<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Data class holding the data sent from the server
	 */
	
	/**
	 * Data class
	 */
	class Data
	{
		/**
		 * Properties of the data
		 */
		protected $fullLine;
		protected $type = 0;
		protected $command;
		protected $commandArgs;
		protected $ident;
		protected $sender;
		protected $receiver;
		protected $origin = 0;
		protected $authLevel = 0;

		/**
		 * Constants relating to type of data, instead of an enumeration
		 */
		const PING = 1;
		const COMMAND = 2;

		/**
		 * Constants relating to origin of message, instead of an enumeration
		 */
		const PRIVMSG = 1;
		const CHANNEL = 2;

		/**
		 * Magic __get function to allow other parts of the bot to access the properties
		 */
		public function __get($property)
		{
			// If found, return
			if(isset($this->$property))
				return $this->$property;
			
			// If not, spawn error
			$trace = debug_backtrace();
			trigger_error(
				'Undefined property via __get(): ' . $property .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE
				);
			return null;
		}

		/**
		 * Constructor, which is passed the raw line fed into the socket
		 *
		 * @param string $rawdata The raw line fed into the socket
		 */
		public function __construct($rawdata)
		{
			$rawdata_array		= explode(" ", $rawdata);

			// Special case if the sent command is a ping
			if($rawdata_array[0] == "PING")
				$this->type = self::PING;

			// Get length of everything before command including last space
			$identlength		= strlen($rawdata_array[0]." ".(isset($rawdata_array[1]) ? $rawdata_array[1] : "")." ".(isset($rawdata_array[2]) ? $rawdata_array[2] : "")." ");
			// Retain all that is in $data after $identlength characters with replaced chr(10)'s and chr(13)'s and minus the first ':'
			$this->fullLine		= substr(str_replace(array(chr(10), chr(13)), '', substr($rawdata, $identlength)), 1);

			// Get the first word of the data as the "command" if it is prefixed by the right prefix
			if($this->fullLine[0] == COMMAND_PREFIX)
			{
				$this->type		= self::COMMAND;
				$_temp_expl		= explode(" ", $this->fullLine);
				$this->command	= substr($_temp_expl[0], 1);
				$this->commandArgs = array_slice($_temp_expl, 1);
			}

			// The username!hostname of the sender (don't include the first ':' - start from 1)
			$this->ident		= substr($rawdata_array[0], 1);

			// Only the username of the sender (one step extra because only that before the ! wants to be parsed)
			$hostlength			= strlen(strstr($rawdata_array[0], '!'));
			$this->sender		= substr($rawdata_array[0], 1, -$hostlength);

			// The receiver of the sent message (either the channelname or the bot's nickname)
			$this->receiver		= (isset($rawdata_array[2]) ? $rawdata_array[2] : BOT_NICKNAME);

			// Interpret the origin of the message received ("PRIVATE" or "CHANNEL") depending on the receiver
			$this->origin		= $this->interpretReceiver($this->receiver);

			// Get whether the user is authenticated
			$this->authLevel 	= $this->isAuthenticated($this->ident);
		}

		/**
		 * Interprets the receiver of a message sent and returns whether it is one from a user (a private message) or one sent in the channel.
		 * 
		 * @access private
		 * @param string $receiver The receiver to be interpretted
		 * @return string $type The type of message sent ("PRIVATE" or "CHANNEL")
		 */
		private function interpretReceiver($receiver)
		{
			// Regular expressions to match "#channel"
			$regex = '/(#)((?:[a-z][a-z]+))/is';
			
			// If the receiver includes a channelname
			if(preg_match($regex, $receiver))
			{
				// ... it was sent to the channel
				$type = self::CHANNEL;
				return $type;
			}
			// Or if the sent message's receiver is the bots nickname
			elseif($receiver == BOT_NICKNAME)
			{
				// ... it is a private message
				$type = self::PRIVMSG;
				return $type;
			}
			else
			{
				return 0;
			}
		}

		/**
		 * Checks if sender of message is in the list of authenticated users. (username!hostname)
		 * 
		 * @access private
		 * @param string $ident The username!hostname of the user we want to check
		 * @return boolean $authenticated TRUE for an authenticated user, FALSE for one which isn't
		 */
		private function isAuthenticated($ident)
		{
			// Fetch the userlist array
			global $users;
			
			// If the lower-case ident is found in the userlist array, return true
			$ident = strtolower($ident);

			if(in_array($ident, $users))
			{
				debug_message("User ($ident) is authenticated.");
				$authenticated = true;
			}
			else
			{
			    debug_message("User ($ident) is not authenticated.");
				$authenticated = false;
			}
			
			return $authenticated;
		}
	}

?>