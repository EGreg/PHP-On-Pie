<?php

/**
 * Pie Bootstrap class
 *
 * @package Pie
 */

class Pie_Bootstrap
{	
	static function setIncludePath()
	{
		$paths = array(APP_DIR, PIE_DIR, get_include_path());
		set_include_path(implode(PS, $paths));
	}
	
	static function registerAutoload()
	{
		spl_autoload_register(array('Pie', 'autoload'));
	}
	
	
	static function registerExceptionHandler()
	{
		self::$prev_exception_handler
		 = set_exception_handler(array('Pie', 'exceptionHandler'));
	}
	
	static function prevExceptionHandler()
	{
		return self::$prev_exception_handler;
	}

	static function registerErrorHandler()
	{
		self::$prev_error_handler
		 = set_error_handler(array('Pie', 'errorHandler'));
	}

	static function prevErrorHandler()
	{
		return self::$prev_error_handler;
	}
	
	static function defineFunctions()
	{
		// We may need to define JSON functions ourselves on PHP < 5.2
		if ( !function_exists('json_decode') ) {
			function json_decode($content, $assoc=false){
				if ( $assoc ){
					$json = new Pie_Json(SERVICES_JSON_LOOSE_TYPE);
				} else {
					$json = new Pie_Json;
				}
				return $json->decode($content);
			}
		}

		if ( !function_exists('json_encode') ) {
			function json_encode($content){
				$json = new Services_JSON;
				return $json->encode($content);
			}
		}
	}

	/**
	 * Used to undo the mangling done by magic_quotes_gpc
	 * @param string $to_strip
	 *  Optional. The string or array to revert.
	 *  If null, reverts all the PHP input arrays.
	 */
	static function revertSlashes($to_strip = null)
	{		
		if (get_magic_quotes_gpc()) {
			if (isset($to_strip)) {
				return is_array($to_strip)
				 ? array_map(array('Pie_Bootstrap', 'revertSlashes'), $to_strip) 
				 : stripslashes($to_strip);
			}
			$_COOKIE = self::revertSlashes($_COOKIE);
			$_FILES = self::revertSlashes($_FILES);
			$_GET = self::revertSlashes($_GET);
			$_POST = self::revertSlashes($_POST);
			$_REQUEST = self::revertSlashes($_REQUEST);
		}
	}
	
	static function setDefaultTimezone()
	{
		$script_tz = Pie_Config::get('pie', 'defaultTimezone', 'America/New_York');
		if (isset($script_tz)) {
			date_default_timezone_set($script_tz);
		}
	}
	
	static function umask()
	{
		umask(0); // All new files will have 777 perms until chmodded
	}
	
	/**
	 * Loads the configuration and plugins in the right order
	 */
	static function configure()
	{
		Pie_Config::load('config/pie.json');
		
		// Get the app config, but don't load it yet
		$app_p = new Pie_Parameters();
		$app_p->load('config/app.json');
		$app_p->load('local/app.json');
		
		// Load all the plugin config files first
		$paths = explode(PS, get_include_path());
		$plugins = $app_p->get('pie', 'plugins', array());
		foreach ($plugins as $plugin) {
			$plugin_path = Pie::realPath('plugins'.DS.$plugin);
			if (!$plugin_path) {
				throw new Pie_Exception_MissingPlugin(compact('plugin'));
			}
			Pie_Config::load($plugin_path.DS.'config'.DS.'plugin.json');
			array_splice($paths, 1, 0, array($plugin_path));
			$PLUGIN = strtoupper($plugin);
			if (!defined($PLUGIN.'_PLUGIN_DIR'))
				define($PLUGIN.'_PLUGIN_DIR', $plugin_path);
			if (!defined($PLUGIN.'_PLUGIN_CONFIG_DIR'))
				define($PLUGIN.'_PLUGIN_CONFIG_DIR', $plugin_path.DS.'config');
			if (!defined($PLUGIN.'_PLUGIN_CLASSES_DIR'))
				define($PLUGIN.'_PLUGIN_CLASSES_DIR', $plugin_path.DS.'classes');
			if (!defined($PLUGIN.'_PLUGIN_FILES_DIR'))
				define($PLUGIN.'_PLUGIN_FILES_DIR', $plugin_path.DS.'files');
			if (!defined($PLUGIN.'_PLUGIN_HANDLERS_DIR'))
				define($PLUGIN.'_PLUGIN_HANDLERS_DIR', $plugin_path.DS.'handlers');
			if (!defined($PLUGIN.'_PLUGIN_PLUGINS_DIR'))
				define($PLUGIN.'_PLUGIN_PLUGINS_DIR', $plugin_path.DS.'plugins');
			if (!defined($PLUGIN.'_PLUGIN_SCRIPTS_DIR'))
				define($PLUGIN.'_PLUGIN_SCRIPTS_DIR', $plugin_path.DS.'scripts');
			if (!defined($PLUGIN.'_PLUGIN_TESTS_DIR'))
				define($PLUGIN.'_PLUGIN_TESTS_DIR', $plugin_path.DS.'tests');
			if (!defined($PLUGIN.'_PLUGIN_WEB_DIR'))
				define($PLUGIN.'_PLUGIN_WEB_DIR', $plugin_path.DS.'web');
		}
		set_include_path(implode(PS, $paths));
		
		// Now, we can merge in our app's config
		Pie_Config::merge($app_p);

		// Now, load any other files we were supposed to load
		$config_files = Pie_Config::get('pie', 'configFiles', array());
		foreach ($config_files as $cf) {
			Pie_Config::load($cf);
		}
		$script_files = Pie_Config::get('pie', 'scriptFiles', array());
		foreach ($script_files as $cf) {
			Pie::includeFile($cf);
		}
	}
	
	/**
	 * Adds the first alias to the configuration
	 */
	static function addAlias()
	{
		Pie_Config::set('pie', 'aliases', '', APP_WEB_DIR);
	}

	protected static $prev_exception_handler;
	protected static $prev_error_handler;
}

if (!function_exists('t')) {
	function t($text)
	{
		// Give handlers a chance to process this text
		Pie::event('pie/text', array(), 'before', $text);
		return $text;
	}
}
