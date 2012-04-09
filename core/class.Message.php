<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Message class enabling the bot and its plugins to send messages to the server, a channel or the client
	 */

	/**
	 * Message class
	 */
	class Message
	{
		/**
		 * The command sent in the message.
		 *
		 * @var string
		 */
		private $command;

		/**
		 * The body of the message.
		 *
		 * @var string
		 */
		private $body;

		/**
		 * The receiver of the message.
		 *
		 * @var string
		 */
		private $receiver;

		/**
		 * Constants relating to type of message. Not used at the moment
		 */
		const COMMAND = 1;
		const MESSAGE = 2;

		/**
		 * __construct()
		 *
		 * @abstract Constructor, allowing a short-hand version of sending a message.
		 *
		 * @return bool If message was successfully sent or not.
		 */
		public function __construct( $cmd = null, $body = null, $rcvr = null )
		{
			$this->command = $cmd;
			$this->body = $this->markup_text( $body );
			$this->receiver = $rcvr;

			if ( $this->send() )
				return true;
		}

		/**
		 * send()
		 *
		 * @abstract Sends the message with defined properties, if they are set.
		 *
		 * @return bool If message was successfully sent or not.
		 */
		public function send()
		{
			// Fetch the socket
			global $socket;

			if ( empty( $this->command ) )
				return false;

			// If it is a command with no body
			if ( empty( $this->body ) ) {
				if ( fputs( $socket, $this->command . "\r\n" ) ) {
					if ( DEBUG_OUTPUT ) {
						debug_message( "Command \"" . $this->command . "\" was sent to the server." );
					}
					return true;
				}
			}
			// If it is a private message
			elseif ( $this->command == "PRIVMSG" )
			{
				if ( fputs( $socket, $this->command . " " . $this->receiver . " :" . $this->body . "\r\n" ) ) {
					if ( DEBUG_OUTPUT ) {
						debug_message( "Command \"" . $this->command . "\" with receiver \"" . $this->receiver . "\" and message \"" . $this->body . "\" was sent to the server." );
					}
					return true;
				}
			}
			// If it is any other kind of message
			else
			{
				if ( fputs( $socket, $this->command . " " . $this->body . "\r\n" ) ) {
					if ( DEBUG_OUTPUT ) {
						debug_message( "Command \"" . $this->command . "\" with message \"" . $this->body . "\" was sent to the server." );
					}
					return true;
				}
			}
		}

		/**
		 * set_command()
		 *
		 * @abstract Sets the command of a message.
		 *
		 * @param string $cmd The command.
		 */
		public function set_command( $cmd )
		{
			$this->command = $cmd;

		}

		/**
		 * set_body()
		 *
		 * @abstract Sets the body of the message.
		 *
		 * @param string $msg The actual message.
		 */
		public function set_body( $msg )
		{
			$this->body = $msg;
		}

		/**
		 * set_receiver()
		 *
		 * @abstract Sets the receiver of the message.
		 *
		 * @param string $rcvr The receiver.
		 */
		public function set_receiver( $rcvr )
		{
			// If there is a space in the receiver
			if ( strpos( $rcvr, ' ' ) !== false ) {
				debug_message( "There cannot be a space in the receiver of the message!" );
			}
			else
			{
				$this->$receiver = $rcvr;
			}
		}

		/**
		 * markup_text()
		 *
		 * @abstract Searches the message for markup that styles the text.
		 *
		 * @access public
		 * @param string $message The message to markup.
		 * @return string $message The marked up message.
		 */
		function markup_text( $message )
		{
			$markups = array(
				'*' => chr( 2 ), // bold (STX)
				'__' => chr( 31 ), // underlined (US)
				'+' => chr( 29 ), // italic (GS)
				'|' => chr( 15 ) // the ending character (SI)
			);

			// Loop throuh all markups and replace when necessary
			foreach ( $markups as $char => $markup )
				$message = str_replace( $char, $markup, $message );

			return $message;
		}
	}

?>