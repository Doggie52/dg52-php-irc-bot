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
		 * Properties
		 */
		private $command;
		private $body;
		private $receiver;

		/**
		 * Constants relating to type of message. Not used at the moment
		 */
		const COMMAND = 1;
		const MESSAGE = 2;

		/**
		 * Constructor, allowing a short-hand version of sending a message
		 */
		public function __construct($cmd = null, $body = null, $rcvr = null)
		{
			$this->command = $cmd;
			$this->body = $this->markup_text($body);
			$this->receiver = $rcvr;

			if(!empty($this->command))
			{
				$this->send();
				return true;
			}
		}

		/**
		 * send
		 *
		 * Sends the message with defined properties, if they are set
		 */
		public function send()
		{
			// Fetch the socket
			global $socket;
			
			// If it is a command with no body
			if(empty($this->body))
			{
				if(fputs($socket, $this->command."\r\n"))
				{
					if(DEBUG_OUTPUT)
					{
						debug_message("Command \"".$this->command."\" was sent to the server.");
					}
					return true;
				}
			}
			// If it is a private message
			elseif($this->command == "PRIVMSG")
			{
				if(fputs($socket, $this->command." ".$this->receiver." :".$this->body."\r\n"))
				{
					if(DEBUG_OUTPUT)
					{
						debug_message("Command \"".$this->command."\" with receiver \"".$this->receiver."\" and message \"".$this->body."\" was sent to the server.");
					}
					return true;
				}
			}
			// If it is any other kind of message
			else
			{
				if(fputs($socket, $this->command." ".$this->body."\r\n"))
				{
					if(DEBUG_OUTPUT)
					{
						debug_message("Command \"".$this->command."\" with message \"".$this->body."\" was sent to the server.");
					}
					return true;
				}
			}
		}

		/**
		 * set_command
		 *
		 * Sets the command of a message
		 * @param string $cmd The command
		 */
		public function set_command($cmd)
		{
			$this->$command = $cmd;
			
		}

		/**
		 * set_body
		 *
		 * Sets the body of the message
		 * @param string $msg The actual message
		 */
		public function set_body($msg)
		{
			$this->$body = $msg;
		}

		/**
		 * set_receiver
		 *
		 * Sets the receiver of the message
		 * @param string $rcvr The receiver
		 */
		public function set_receiver($rcvr)
		{
			// If there is a space in the receiver
			if(strpos($rcvr, ' ') !== false)
			{
				debug_message("There cannot be a space in the receiver of the message!");
			}
			else
			{
				$this->$receiver = $rcvr;
			}
		}

		/**
		 * Searches the message for markup that styles the text
		 *
		 * @todo Allow multiple styles to applied to same text
		 * 
		 * @access public
		 * @param string $message The message to markup
		 * @return string $message The marked up message
		 */
		function markup_text($message)
		{
			// Matches for anything, then the markup character (denoted with %), then the text to be marked up, the ending markup character (%), then anything
			$templ_regex = '/.*?(\\%)(.*?)(\\%).*?/is';

			foreach(array('*' => /*STX*/ chr(2), '_' => /*US*/ chr(31), '+' => /*GS*/ chr(29)) as $char => $irc_markup)
			{
				// Prepare the regex
				$regex = str_replace('%', $char, $templ_regex);

				if($matches = preg_match_all($regex, $message, $matches))
				{
					// Change first markup character to $irc_markup
					$pos = strpos($message, $char);
					if ($pos !== false)
					{
						$message = substr_replace($message, $irc_markup, $pos, strlen($char));
					}

					// Rid ourselves of the markup characters and end the marking up
					$pos = strpos($message, $char);
					if ($pos !== false)
					{
						$message = substr_replace($message, /*SI*/ chr(15), $pos, strlen($char));
					}
				}
			}
			
			return $message;
		}
	}
?>