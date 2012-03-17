<?php
/**
 * HelpLibrary plugin
 * 
 * Displays help about available functions to different users.
 * The plugin is unfinished for now, the plan is to build a system that shows users the commands available to them. In the end, this plugin will replace "speech.php"
 */

	class HelpLibrary extends PluginEngine
	{
		public $PLUGIN_NAME = "Help Library";
		public $PLUGIN_AUTHOR = "Doggie52";
		public $PLUGIN_DESCRIPTION = "Displays help about functions.";
		public $PLUGIN_VERSION = "1.0";

		/**
		 * A local variable to hold a copy of the code documentation
		 */
		private $commandDocumentation = array();

		public function __construct()
		{
			$this->register_action('load', array('HelpLibrary', 'init'));
			$this->register_command('help', array('HelpLibrary', 'help'));
		}

		/**
		 * Shows help for a specific command or lists all command available to the user
		 */
		public function help($data)
		{
			// If no specific function has been specified
			if(empty($data->commandArgs))
			{
				$this->list_all_commands($data);
			}
			else
			{
				$this->show_help_command($data);
			}
		}

		/**
		 * Lists all commands that are available to the user
		 */
		public function list_all_commands($data)
		{
			$_msg = new Message("PRIVMSG", "*ALL COMMANDS*", $data->sender);
			switch($data->authLevel)
			{
				case 1:
					$_msg = new Message("PRIVMSG", "+Access level needed+", $data->sender);
					foreach($this->commandDocumentation as $command => $documentation)
					{
						// Only show commands where auth level is needed
						if($documentation['auth_level'] != 1)
							break;

						// If this is a single-line documentation
						if(!is_array($documentation['documentation']))
						{
							$line = "*".strtoupper($documentation['access_type']).":* ";
							$line .= "+".$command."+ ";
							$line .= "- ".$documentation['documentation'];

							$_msg = new Message("PRIVMSG", $line, $data->sender);
						}
						else
						{
							$line = "*".strtoupper($documentation['access_type']).":* ";
							$line .= "+".$command."+ ";
							// Print first line
							$line .= "- ".$documentation['documentation'][0];
							
							$_msg = new Message("PRIVMSG", $line, $data->sender);

							// For every next line, i.e. starting at entry 1
							for($i = 1; $i <= count($documentation['documentation']); $i++)
							{
								$_msg = new Message("PRIVMSG", $documentation['documentation'][$i], $data->sender);
							}
						}
					}
					// No break here, to enable authenticated users to see all commands
				case 0:
					$_msg = new Message("PRIVMSG", "+No access level needed+", $data->sender);
					foreach($this->commandDocumentation as $command => $documentation)
					{
						// Only show commands where auth level isn't needed
						if($documentation['auth_level'] != 0)
							break;

						// If this is a single-line documentation
						if(!is_array($documentation['documentation']))
						{
							$line = "*".strtoupper($documentation['access_type']).":* ";
							$line .= "+".$command."+ ";
							$line .= "- ".$documentation['documentation'];

							$_msg = new Message("PRIVMSG", $line, $data->sender);
						}
						else
						{
							$line = "*".strtoupper($documentation['access_type']).":* ";
							$line .= "+".$command."+ ";
							// Print first line
							$line .= "- ".$documentation['documentation'][0];
							
							$_msg = new Message("PRIVMSG", $line, $data->sender);

							// For every next line, i.e. starting at entry 1
							for($i = 1; $i <= count($documentation['documentation']); $i++)
							{
								$_msg = new Message("PRIVMSG", $documentation['documentation'][$i], $data->sender);
							}
						}
					}
					break;
			}
		}
		
		/**
		 * Copies the available commands and their documentation to the plugin, sorting it by the command name
		 */
		public function init()
		{
			$this->commandDocumentation = PluginHandler::$documentation;
			sort($this->commandDocumentation);
		}
	}
?>