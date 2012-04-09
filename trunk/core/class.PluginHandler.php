<?php
	/**
	 * dG52 PHP IRC Bot
	 *
	 * @author: Douglas Stridsberg
	 * @email: doggie52@gmail.com
	 * @url: www.douglasstridsberg.com
	 *
	 * Handles plugins.
	 */

	class PluginHandler
	{
		/**
		 * A static list of plugin objects, available to access by any plugin
		 */
		static public $plugins = array();

		/**
		 * A static list of plugin hooks with an array each to stored the callbacks
		 */
		static public $hooks = array(
			'load' => array(), // called when bot has loaded
			'connect' => array(), // called when bot has successfully connected
			'disconnect' => array(), // called when bot has disconnected
			'private_message' => array(), // called when bot receives a private message
			'channel_message' => array(), // called when the channel the bot is in receivs a message
		);

		/**
		 * A static list of the commands registered by all plugins
		 *
		 * Structure:
		 * array( 'command name' => array(
		 *								 'plugin name',
		 *								 'command function name'
		 *								 )
		 */
		static public $commands = array();

		/**
		 * A static list of the commands together with their documentation
		 *
		 * Structure:
		 * array( 'command name' => array(
		 *								 'auth_level' => 0/1,
		 *								 'access_type' => 'pm'/'channel'/'both'
		 *								 'documentation' => 'single line'/array(
		 *																	 'multiple',
		 *																	 'lines'
		 *																	 )
		 *								 )
		 * )
		 */
		static public $documentation = array();

		private function __construct()
		{

		}

		/**
		 * Loads all plugins and appends them to array
		 */
		static public function load_plugins()
		{
			include( "class.PluginEngine.php" );
			foreach ( glob( "core/plugins/*.php" ) as $pluginName )
			{
				include_once( $pluginName );
				// Get the plugin name without the .php
				$pluginName = basename( $pluginName, ".php" );
				self::$plugins[$pluginName] = new $pluginName;
			}
		}

		/**
		 * Triggers an event notification based on parameters
		 *
		 * @access public
		 * @param string $hook Name of the hook to fire
		 * @param object $data (optional) The data object to pass to plugins
		 * @return void
		 */
		static public function trigger_hook( $hook, $data = null )
		{
			// Does the hook exist?
			if ( !isset( self::$hooks[$hook] ) )
				return;

			// Fire the events
			foreach ( self::$hooks[$hook] as $callback )
				call_user_func_array( array( self::$plugins[$callback[0]], $callback[1] ), array( $data ) );
		}

		/**
		 * Runs a command
		 *
		 * @access public
		 * @param string $command Name of the command to fire
		 * @param object $data The data object to pass to plugins
		 * @return bool Whether or not the command was run successfully
		 */
		static public function run_command( $command, $data )
		{
			// Does the hook exist?
			if ( !isset( self::$commands[$command] ) )
				return false;

			// Does the user have the necessary privileges?
			if ( self::$documentation[$command]['auth_level'] > $data->authLevel ) {
				$_msg = new Message( "PRIVMSG", "You do not have sufficient privileges to run +{$command}|!", $data->sender );
				return false;
			}

			// Fire the callback associated with the command
			$callback = self::$commands[$command];
			call_user_func_array( array( self::$plugins[$callback[0]], $callback[1] ), array( $data ) );
		}

	}

?>