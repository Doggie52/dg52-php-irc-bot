<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Data class holding the data sent from the server.
	 */

	/**
	 * Data class.
	 */
	class Data
	{
		/**
		 * Holds the entire text sent by the server or user (but without the ident).
		 *
		 * @var string
		 */
		protected $fullLine;

		/**
		 * Type of data, if set to something it will be either a PING or a command.
		 *
		 * @var int
		 */
		protected $type = 0;

		/**
		 * Numeric reply code sent from the server, if set.
		 *
		 * @var int
		 */
		protected $replyCode = 0;

		/**
		 * The command sent by a user.
		 *
		 * @var string
		 */
		protected $command;

		/**
		 * Array of arguments to the above command.
		 *
		 * @var array
		 */
		protected $commandArgs;

		/**
		 * The ident of the user sending the message.
		 *
		 * @var string
		 */
		protected $ident;

		/**
		 * The nickname of the user sending the command.
		 *
		 * @var string
		 */
		protected $sender;

		/**
		 * The receiver of the message, either the channel name of the bot's name.
		 *
		 * @var string
		 */
		protected $receiver;

		/**
		 * The origin of the message (either channel or PM).
		 *
		 * @var int
		 */
		protected $origin = 0;

		/**
		 * The authentication level of the user sending the message.
		 *
		 * @var int
		 */
		protected $authLevel = 0;

		/**
		 * Constants relating to type of data, instead of an enumeration.
		 */
		const PING = 1;
		const COMMAND = 2;

		/**
		 * Constants relating to origin of message, instead of an enumeration.
		 */
		const PM = 1;
		const CHANNEL = 2;

		/**
		 * Constants relating to numeric reply code sent from the server.
		 */
		const RPL_WELCOME = 001;
		const RPL_ENDOFMOTD = 376;
		const ERR_NICKNAMEINUSE = 433;

		/**
		 * __get()
		 *
		 * @abstract Magic __get function to allow other parts of the bot to access the properties.
		 */
		public function __get( $property )
		{
			// If found, return
			if ( isset( $this->$property ) )
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
		 * __construct()
		 *
		 * @abstract Constructor, which is passed the raw line fed into the socket.
		 *
		 * @param string $rawdata The raw line fed into the socket.
		 */
		public function __construct( $rawdata )
		{
			$rawdata_array = explode( " ", $rawdata );

			// Special case if the sent command is a ping
			if ( $rawdata_array[0] == "PING" )
				$this->type = self::PING;

			// Does this include a numeric, 3 digit reply code?
			if ( isset( $rawdata_array[1] ) && strlen( $rawdata_array[1] ) == 3 && is_numeric( $rawdata_array[1] ) )
				$this->replyCode = $rawdata_array[1];

			// Get length of everything before command including last space
			$identlength = strlen( $rawdata_array[0] . " " . ( isset( $rawdata_array[1] ) ? $rawdata_array[1] : "" ) . " " . ( isset( $rawdata_array[2] ) ? $rawdata_array[2] : "" ) . " " );
			// Retain all that is in $data after $identlength characters with replaced chr(10)'s and chr(13)'s and minus the first ':'
			$this->fullLine = substr( str_replace( array( chr( 10 ), chr( 13 ) ), '', substr( $rawdata, $identlength ) ), 1 );

			// Get the first word of the data as the "command" if it is prefixed by the right prefix
			if ( $this->fullLine[0] == COMMAND_PREFIX ) {
				$this->type = self::COMMAND;
				$_temp_expl = explode( " ", $this->fullLine );
				$this->command = substr( $_temp_expl[0], 1 );
				$this->commandArgs = array_slice( $_temp_expl, 1 );
			}

			// The username!hostname of the sender (don't include the first ':' - start from 1)
			$this->ident = substr( $rawdata_array[0], 1 );

			// Only the username of the sender (one step extra because only that before the ! wants to be parsed)
			$hostlength = strlen( strstr( $rawdata_array[0], '!' ) );
			$this->sender = substr( $rawdata_array[0], 1, -$hostlength );

			// The receiver of the sent message (either the channelname or the bot's nickname)
			$this->receiver = ( isset( $rawdata_array[2] ) ? $rawdata_array[2] : BOT_NICKNAME );

			// Interpret the origin of the message received ("PRIVATE" or "CHANNEL") depending on the receiver
			$this->origin = $this->interpretReceiver( $this->receiver );

			// Get whether the user is authenticated
			$this->authLevel = $this->isAuthenticated( $this->ident );
		}

		/**
		 * interpretReceiver()
		 *
		 *
		 * @abstract Interprets the receiver of a message sent and returns whether it is one from a user (a private message) or one sent in the channel.
		 *
		 * @access private
		 * @param string $receiver The receiver to be interpretted.
		 * @return int $type The type of message sent ("PRIVATE" or "CHANNEL").
		 */
		private function interpretReceiver( $receiver )
		{
			// Regular expressions to match "#channel"
			$regex = '/(#)((?:[a-z][a-z]+))/is';

			// If the receiver includes a channelname
			if ( preg_match( $regex, $receiver ) ) {
				// ... it was sent to the channel
				$type = self::CHANNEL;
				return $type;
			}
			// Or if the sent message's receiver is the bots nickname
			elseif ( $receiver == BOT_NICKNAME )
			{
				// ... it is a private message
				$type = self::PM;
				return $type;
			}
			else
			{
				return 0;
			}
		}

		/**
		 * isAuthenticated()
		 *
		 * @abstract Checks if sender of message is in the list of authenticated users. (username!hostname)
		 *
		 * @access private
		 * @param string $ident The username!hostname of the user we want to check.
		 * @return int $level 1 for an authenticated user, 0 for one which isn't.
		 */
		private function isAuthenticated( $ident )
		{
			// Fetch the userlist array
			global $users;

			// If the lower-case ident is found in the userlist array, return true
			$ident = strtolower( $ident );

			if ( in_array( $ident, $users ) ) {
				debug_message( "User ($ident) is authenticated." );
				$level = 1;
			}
			else
			{
				$level = 0;
			}

			return $level;
		}
	}

?>