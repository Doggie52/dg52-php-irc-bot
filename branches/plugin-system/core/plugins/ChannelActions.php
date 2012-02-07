<?php
/**
 * Channel Actions
 * 
 * Enables joining and parting channels, setting topics as well as converting to channelnames. Also allows user to see currently connected channels.
 */

	class ChannelActions extends PluginEngine
	{
		public $PLUGIN_NAME = "Channel Actions";
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
		public function joinInitialChannels()
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
		public function partAllChannels()
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
		
		/*public function onCommand($_DATA)
		{
			$commandArray = explode(" ", $_DATA['fullCommand']);
			if($_DATA['authLevel'] == 1 && $_DATA['messageType'] == "PRIVATE")
			{
				switch(strtolower($commandArray[0]))
				{
					case "join":
						if($this->joinChannel($commandArray[1]))
						{
							$this->display("Channel ".$this->toChannel($commandArray[1])." was joined!", $_DATA['sender']);
						}
						break;
					case "part":
						if($this->partChannel($commandArray[1]))
						{
							$this->display("Channel ".$this->toChannel($commandArray[1])." was parted!", $_DATA['sender']);
						}
						break;
					case "listchannels":
						$this->display("Currently connected channels:", $_DATA['sender']);
						foreach($this->connectedChannels as $channelName)
						{
							$this->display("-> ".$channelName, $_DATA['sender']);
						}
						break;
				}
			}
			
			if($_DATA['authLevel'] == 1)
			{
				switch(strtolower($commandArray[0]))
				{
					case "topic":
						if($_DATA['messageType'] == "PRIVATE")
						{
							$channelName = $this->toChannel($commandArray[1]);
							$topic = substr($command, strlen($commandArray[0]." ".$commandArray[1]." "));
							$this->setTopic($channelName, $topic);
						}
						elseif($_DATA['messageType'] == "CHANNEL")
						{
							$channelName = $_DATA['receiver'];
							$topic = substr($command, strlen($commandArray[0]." "));
							$this->setTopic($channelName, $topic);
						}
						break;
					case "op":
					case "deop":
					case "voice":
					case "devoice":
						$username = $commandArray[1];
						if($_DATA['messageType'] == "PRIVATE")
						{
							$channel = $commandArray[2];
						}
						switch(strtolower($commandArray[0]))
						{
							case "op":
								$this->setUserMode($_DATA['sender'], $_DATA['receiver'], "+o");
								break;
							case "deop":
								$this->setUserMode($_DATA['sender'], $_DATA['receiver'], "-o");
								break;
							case "voice":
								$this->setUserMode($_DATA['sender'], $_DATA['receiver'], "+v");
								break;
							case "devoice":
								$this->setUserMode($_DATA['sender'], $_DATA['receiver'], "-v");
								break;
						}
				}
			}
		}*/

		public function __construct()
		{
			$this->register_action('connect', array('ChannelActions', 'joinInitialChannels'));
			$this->register_action('disconnect', array('ChannelActions', 'partAllChannels'));
		}
	}

?>