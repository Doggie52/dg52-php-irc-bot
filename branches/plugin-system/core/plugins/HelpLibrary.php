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
			$this->register_action('load', array('HelpLibrary', 'store_documentation'));
			$this->register_command('help', array('HelpLibrary', 'help'));
		}

		/**
		 * Shows help for a specific command or lists all command available to the user
		 */
		public function help($data)
		{
			// If no specific function has been specified
			if(!isset($data->commandArgs[0]))
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
			$line = '';
			switch($data->authLevel)
			{
				case 1:
					$_msg = new Message("PRIVMSG", "+Access level needed+", $data->sender);
					foreach($this->commandDocumentation as $command => $documentation)
					{
						// Only show commands where auth level is needed
						if($documentation['auth_level'] != 1)
							continue;

						// List the commands in a string
						$line .= $command.", ";
					}

					$line = substr($line, 0, -2);

					$_msg = new Message("PRIVMSG", $line, $data->sender);
					// No break here, to enable authenticated users to see all commands
				case 0:
					$line = '';
					$_msg = new Message("PRIVMSG", "+No access level needed+", $data->sender);
					foreach($this->commandDocumentation as $command => $documentation)
					{
						// Only show commands where auth level isn't needed
						if($documentation['auth_level'] != 0)
							continue;

						// List the commands in a string
						$line .= $command.", ";
					}

					$line = substr($line, 0, -2);

					$_msg = new Message("PRIVMSG", $line, $data->sender);
					break;
			}

			$_msg = new Message("PRIVMSG", "+For more information, type !help <command>.+", $data->sender);
		}

		/**
		 * Shows help for a specific command
		 */
		public function show_help_command($data)
		{
			// Get the first word/command
			$command = $data->commandArgs[0];

			// Checks if command is available
			if(empty($this->commandDocumentation[$command]))
			{
				$_msg = new Message("PRIVMSG", "The command was not found!", $data->sender);
				return;
			}

			// Checks if command is available for the user's authentication level
			if($this->commandDocumentation[$command]['auth_level'] > $data->authLevel)
			{
				$_msg = new Message("PRIVMSG", "This command is not available for you!", $data->sender);
				return;
			}

			// The command documentation is now ready to be displayed, check if it is a single-line one
			if(!is_array($this->commandDocumentation[$command]['documentation']))
			{
				$line = "*".strtoupper($this->commandDocumentation[$command]['access_type']).":* ";
				$line .= "+".$command."+ ";
				$line .= "- ".$this->commandDocumentation[$command]['documentation'];

				$_msg = new Message("PRIVMSG", $line, $data->sender);
			}
			else
			{
				$line = "*".strtoupper($this->commandDocumentation[$command]['access_type']).":* ";
				$line .= "+".$command."+ ";
				// Print first line
				$line .= "- ".$this->commandDocumentation[$command]['documentation'][0];
				
				$_msg = new Message("PRIVMSG", $line, $data->sender);

				// For every next line, i.e. starting at entry 1
				for($i = 1; $i < count($this->commandDocumentation[$command]['documentation']); $i++)
				{
					$_msg = new Message("PRIVMSG", $this->commandDocumentation[$command]['documentation'][$i], $data->sender);
				}
			}
		}
		
		/**
		 * Copies the available commands and their documentation to the plugin, sorting it by the command name
		 */
		public function store_documentation()
		{
			$this->commandDocumentation = PluginHandler::$documentation;
			ksort($this->commandDocumentation);
		}
	}
?>