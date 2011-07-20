<?php
/**
 * Channel Actions
 * 
 * Enables joining and parting channels, setting topics as well as converting to channelnames. Also allows user to see currently connected channels.
 */

	class ChannelActions extends aPlugin
	{
		public $PLUGIN_NAME = "Channel Actions";
		public $PLUGIN_ID = "ChannelActions";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Enables joining and parting channels.";
		public $PLUGIN_VERSION = "1.0";
		
		/**
		 * The array of currently joined channels
		 */
		public $connectedChannels = array();
		
		/**
		 * Connects the bot to all channels specified in the configuration
		 */
		public function onConnect()
		{
			$initialChannels = explode(" ", BOT_CHANNELS);
			foreach($initialChannels as $channelName)
			{
				$this->joinChannel($channelName);
			}
		}
		
		/**
		 * Part all channels
		 */
		public function onDisconnect()
		{
			foreach($this->connectedChannels as $channelName)
			{
				$this->partChannel($channelName);
			}
		}
		
		/**
		 * Joins a channel. Checks if the channel-name includes a #-sign
		 *
		 * @access public
		 * @param string $channel The channels you wish the bot to join
		 * @return boolean Whether the joining was successful or not
		 */
		public function joinChannel($channel)
		{
			$channel = $this->toChannel($channel);
			if(send_data("JOIN", $channel))
			{
				debug_message("Channel ".$channel." was joined!");
				// Add joined channel to array
				$this->connectedChannels[] = $channel;
				return TRUE;
			}
		}
		
		/**
		 * Parts (leaves) a channel. Checks if the channel-name includes a #-sign.
		 *
		 * @access public
		 * @param string $channel The channel you wish the bot to part
		 * @return boolean Whether the parting was successful or not
		 */
		public function partChannel($channel)
		{
			$channel = $this->toChannel($channel);
			if(send_data("PART", $channel))
			{
				debug_message("Channel ".$channel." was parted!");
				// Rid the array of the parted channel
				$this->connectedChannels = remove_item_by_value($channel, $this->connectedChannels);
				return TRUE;
			}
		}
		
		/**
		 * Sets the topic of the specified channel.
		 * 
		 * @access public
		 * @param string $channel The channel you wish to change topic of
		 * @param string $topic The new topic to change to
		 * @return void
		 */
		public function setTopic($channel, $topic)
		{
			$channel = $this->toChannel($channel);
			send_data("TOPIC", $channel." :".$topic);
			debug_message("Channel topic for ".$channel." was altered to \"".$topic."\"!");
		}
		
		/**
		 * Sets a certain mode on the specified user if the bot has the right to do this.
		 *
		 * @access public
		 * @param string $username The username of that to which to apply the command
		 * @param string $channel The channel in which to apply the mode
		 * @param string $mode The desired mode
		 * @return void
		 */
		public function setUserMode($username, $channel, $mode)
		{
			$channel = $this->toChannel($channel);
			send_data("MODE", $channel." ".$mode." ".$username);
		}
		
		/**
		 * Converts input to a proper channel-name if it isn't already
		 *
		 * @access public
		 * @param string $channel The channelname to be converted
		 * @return string $channel The converted channel
		 */
		public function toChannel($channel)
		{
			if($channel[0] != "#")
			{
				$channel = "#".$channel;
			}
			return $channel;
		}
		
		public function onCommand($command, $type, $from, $channel, $authLevel)
		{
			$commandArray = explode(" ", $command);
			if($authLevel == 1 && $type == "PRIVATE")
			{
				switch(strtolower($commandArray[0]))
				{
					case "join":
						if($this->joinChannel($commandArray[1]))
						{
							$this->display("Channel ".$this->toChannel($commandArray[1])." was joined!", $from);
						}
						break;
					case "part":
						if($this->partChannel($commandArray[1]))
						{
							$this->display("Channel ".$this->toChannel($commandArray[1])." was parted!", $from);
						}
						break;
					case "listchannels":
						$this->display("Currently connected channels:", $from);
						foreach($this->connectedChannels as $channelName)
						{
							$this->display("-> ".$channelName, $from);
						}
						break;
				}
			}
			
			if($authLevel == 1)
			{
				switch(strtolower($commandArray[0]))
				{
					case "topic":
						if($type == "PRIVATE")
						{
							$channelName = $this->toChannel($commandArray[1]);
							$topic = substr($command, strlen($commandArray[0]." ".$commandArray[1]." "));
							$this->setTopic($channelName, $topic);
						}
						elseif($type == "CHANNEL")
						{
							$channelName = $channel;
							$topic = substr($command, strlen($commandArray[0]." "));
							$this->setTopic($channelName, $topic);
						}
						break;
					case "op":
					case "deop":
					case "voice":
					case "devoice":
						$username = $commandArray[1];
						if($type == "PRIVATE")
						{
							$channel = $commandArray[2];
						}
						switch(strtolower($commandArray[0]))
						{
							case "op":
								$this->setUserMode($username, $channel, "+o");
								break;
							case "deop":
								$this->setUserMode($username, $channel, "-o");
								break;
							case "voice":
								$this->setUserMode($username, $channel, "+v");
								break;
							case "devoice":
								$this->setUserMode($username, $channel, "-v");
								break;
						}
				}
			}
		}
	}

?>