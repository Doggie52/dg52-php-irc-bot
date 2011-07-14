<?php
/**
 * Channel Actions
 * 
 * Enables joining and parting channels, as well as converting to channelnames. Also allows user to see currently connected channels.
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
		
		function onLoad()
		{
		}
		
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
			$command = explode(" ", $command);
			if($type == "PRIVATE")
			{
				if(strtolower($command[0]) == "join")
				{
					if($this->joinChannel($command[1]))
					{
						$this->display("Channel ".$this->toChannel($command[1])." was joined!", $from);
					}
				}
				elseif(strtolower($command[0]) == "part")
				{
					if($this->partChannel($command[1]))
					{
						$this->display("Channel ".$this->toChannel($command[1])." was parted!", $from);
					}
				}
				elseif(strtolower($command[0]) == "listchannels")
				{
					$this->display("Currently connected channels:", $from);
					foreach($this->connectedChannels as $channelName)
					{
						$this->display("-> ".$channelName, $from);
					}
				}
			}
		}
	}

?>