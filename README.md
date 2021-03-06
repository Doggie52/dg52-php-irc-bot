# Welcome to the dG52 PHP IRC Bot

The **dG52 PHP IRC Bot** is an IRC bot coded in _object-oriented PHP_. Features are added all the time whilst still keeping the base of the bot _clear_, _fast_ and _easy-to-use_.

The bot is built with _modularity_ in mind, allowing developers to build their own plugins to extend the bot's functionality.

## How to run
### Requirements
 * PHP version 5.2 or greater
 * `php_sockets.dll` extension installed and enabled

### Installing
 * Configure the bot by renaming `cfg/config.dist.php` to `cfg/config.php` and changing the settings.
 * Add yourself as an administrator by renaming `cfg/users.dist.inc` to `cfg/users.inc` and, on a new line, entering your details in the following way:
  `<nickname>!<hostname>`
   To find your hostname, simply type `/whois <nickname>` on an IRC server.
 * Run the bot by entering the directory you checked out the repository to and
   * in **\*NIX:** execute `sh run` in your terminal;
   * in **Windows:** open `run.bat`.

You're done! If you get any errors, please create an issue in the tracker and attach the relevant log-file, along with observations of what you were trying to do at the time the error occurred.

## To-do
 * make sure quit messages are displayed
 * create a how-to guide to ease the learning curve for new users
 * create a reference of all the bot's commands
 * create documentation for the plugin API

## Notes
The base of this bot is adapted from http://www.dreamincode.net/forums/topic/82278-creating-an-irc-bot-in-php/. IRC RFC used can be found at http://www.irchelp.org/irchelp/rfc/chapter6.html.
