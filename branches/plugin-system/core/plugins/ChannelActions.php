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

		public function __construct()
		{
			$this->register_action('connect', array('ChannelActions', 'join_initial_channels'));
			$this->register_action('disconnect', array('ChannelActions', 'part_all_channels'));

			$this->register_command('join', array('ChannelActions', 'join_channel'));
			$this->register_command('part', array('ChannelActions', 'part_channel'));
			$this->register_command('topic', array('ChannelActions', 'set_topic'));
			$this->register_command('op', array('ChannelActions', 'set_user_mode'));
			$this->register_command('deop', array('ChannelActions', 'set_user_mode'));
			$this->register_command('voice', array('ChannelActions', 'set_user_mode'));
			$this->register_command('devoice', array('ChannelActions', 'set_user_mode'));
		}
		
		/**
		 * Connects the bot to all channels specified in the configuration
		 */
		public function join_initial_channels()
		{
			$initialChannels = explode(" ", BOT_CHANNELS);
			foreach($initialChannels as $channelName)
			{
				$this->join_channel('', $channelName);
			}
		}
		
		/**
		 * Part all channels
		 */
		public function part_all_channels()
		{
			foreach($this->connectedChannels as $channelName)
			{
				$this->part_channel('', $channelName);
			}
		}
		
		/**
		 * Joins a channel. Checks if the channel-name includes a #-sign
		 *
		 * @param string $channel (optional) The name of the channel, if called directly
		 * @access public
		 */
		public function join_channel($data, $channel)
		{
			if(is_object($data) && @$data->authLevel != 1)
				return;

			if(!isset($channel))
				$channel = $this->to_channel($data->commandArgs[0]);
			else
				$channel = $this->to_channel($channel);
			
			if($_join = new Message("JOIN", $channel))
			{
				debug_message("Channel ".$channel." was joined!");
				// Add joined channel to array
				$this->connectedChannels[] = $channel;
				return;
			}
		}
		
		/**
		 * Parts (leaves) a channel. Checks if the channel-name includes a #-sign.
		 *
		 * @param string $channel (optional) The name of the channel, if called directly
		 * @access public
		 */
		public function part_channel($data, $channel)
		{
			if(isset($data) && @$data->authLevel != 1)
				return;
			
			if(!isset($channel))
				$channel = $this->to_channel($data->commandArgs[0]);
			else
				$channel = $this->to_channel($channel);
			
			if($_part = new Message("PART", $channel))
			{
				debug_message("Channel ".$channel." was parted!");
				// Rid the array of the parted channel
				$this->connectedChannels = remove_item_by_value($channel, $this->connectedChannels);
				return;
			}
		}
		
		/**
		 * Sets the topic of the specified channel.
		 * 
		 * @access public
		 */
		public function set_topic($data)
		{
			if($data->authLevel != 1)
				return;
			
			if($data->origin == Data::PRIVMSG)
			{
				$channel = $this->to_channel($data->commandArgs[0]);
				$topic = substr($data->fullLine, strlen(COMMAND_PREFIX.$data->command." ".$data->commandArgs[0]." "));
			}
			elseif($data->origin == Data::CHANNEL)
			{
				$channel = $data->receiver;
				$topic = substr($data->fullLine, strlen(COMMAND_PREFIX.$data->command." "));
			}

			$channel = $this->to_channel($channel);
			$_msg = new Message("TOPIC", $channel." :".$topic);
			debug_message("Channel topic for ".$channel." was altered to \"".$topic."\"!");
		}
		
		/**
		 * Sets a certain mode on the specified user if the bot has the right to do this
		 * For now, only allows setting modes from contact in a channel
		 *
		 * @access public
		 */
		public function set_user_mode($data)
		{
			if($data->authLevel != 1)
				return;
			
			if($data->origin == Data::CHANNEL)
			{
				switch($data->command)
				{
					case "op":
						$mode = "+o";
						break;
					case "deop":
						$mode = "-o";
						break;
					case "voice":
						$mode = "+v";
						break;
					case "devoice":
						$mode = "-v";
						break;
				}

				$channel = $this->to_channel($data->receiver);
				$username = $data->commandArgs[0];
				$_msg = new Message("MODE", $channel." ".$mode." ".$username);
			}
		}
		
		/**
		 * Converts input to a proper channel-name if it isn't already
		 *
		 * @access public
		 * @param string $channel The channelname to be converted
		 * @return string $channel The converted channel
		 */
		public function to_channel($channel)
		{
			if($channel[0] != "#")
			{
				$channel = "#".$channel;
			}
			return $channel;
		}
	}

?>