<?php

/**
 * Class for routing
 * @package Pie
 */
class Pie_Request
{	
	/**
	 * Get the base URL, possibly with a controller script
	 * @param bool $with_possible_controller
	 *  Defaults to false. If this is true, and if the URL contains 
	 *  the controller script, then the controller script is included 
	 *  in the return value. You can also pass a string here, which
	 *  will then be simply appended as the controller.
	 */
	static function baseUrl(
	 $with_possible_controller = false)
	{
		if (isset(self::$base_url)) {
			if (is_string($with_possible_controller)) {
				if (empty($with_possible_controller)) {
					return self::$app_root_url;
				}
				return self::$app_root_url . "/" . $with_possible_controller;
			}
			if ($with_possible_controller) {
				return self::$base_url;
			}
			return self::$app_root_url;
		}
		
		if (isset($_SERVER['SERVER_NAME'])) {
			// This is a web request, so we can automatically determine
			// the app root URL. If you want the canonical one which the developer
			// may have specified in the config field "pie"/"web"/"appRootUrl"
			// then just query it via Pie_Config::get().
			
			// Infer things
			if (defined('APP_CONTROLLER_URL')) {
				self::$controller_url = APP_CONTROLLER_URL;
			} else {
				self::$controller_url = self::inferControllerUrl();
			}

			// Get the app root URL
			self::$app_root_url = self::getAppRootUrl();

			// Automatically figure out whether to omit 
			// the controller name from the url
			self::$controller_present = (0 == strncmp(
				$_SERVER['REQUEST_URI'], 
				$_SERVER['SCRIPT_NAME'], 
				strlen($_SERVER['SCRIPT_NAME']) 
			));

			self::$base_url = (self::$controller_present)
				? self::$controller_url
				: self::$app_root_url;
		} else {
			// This is not a web request, and we absolutely need
			// the canonical app root URL to have been specified.
			
			$ar = Pie_Config::get('pie', 'web', 'appRootUrl', false);
			if (!$ar) {
				throw new Pie_Exception_MissingConfig(array(
					'fieldpath' => 'pie/web/appRootUrl'
				));
			}
			$cs = Pie_Config::get('pie', 'web', 'controllerSuffix', '');
			self::$app_root_url = $ar;
			self::$controller_url = $ar . $cs;
			self::$controller_present = false;
			self::$base_url = self::$app_root_url;
		}
		
		if (is_string($with_possible_controller)) {
			return self::$app_root_url . "/" . $with_possible_controller;
		}
		if ($with_possible_controller) {
			return self::$base_url;
		}
		return self::$app_root_url;
	}
	
	/**
	 * Get the URL that was requested, possibly with a querystring
	 * @param mixed $query_fields
	 *  If true, includes the entire querystring as requested.
	 *  If a string, appends the querystring correctly to the current URL.
	 *  If an associative array, adds these fields, with their values
	 *  to the existing querystring, while subtracting the fields corresponding
	 *  to null values in $query. Then generates a querystring and
	 *  includes it with the URL.
	 * @return string
	 *  Returns the URL that was requested, possibly with a querystring.
	 */
	static function url(
	 $query_fields = array())
	{
		if (!isset($_SERVER['REQUEST_URI'])) {
			// this was not requested from the web
			return null;
		}
		$request_uri = $_SERVER['REQUEST_URI'];
		
		// Deal with the querystring
		$r_parts = explode('?', $request_uri);
		$request_uri = $r_parts[0];
		$request_querystring = isset($r_parts[1]) ? $r_parts[1] : '';
		
		// Extract the URL
		if (!isset(self::$url)) {
			self::$url = sprintf('http%s://%s%s%s%s%s%s', 
				isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == TRUE ? 's' : '', 
				isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '',
				isset($_SERVER['PHP_AUTH_PW']) ? ':'.$_SERVER['PHP_AUTH_PW'] : '',
				isset($_SERVER['PHP_AUTH_USER']) ? '@' : '',
				$_SERVER['SERVER_NAME'], 
				$_SERVER['SERVER_PORT'] != (isset($_SERVER['HTTPS']) ? 443 : 80) 
					? ':'.$_SERVER['SERVER_PORT'] : '',
				$request_uri);
		}
				
		if (!$query_fields) {
			return self::$url;
		}
		
		$query = array();
		if ($request_querystring) {
			parse_str($request_querystring, $query);
		}
		if (is_string($query_fields)) {
			parse_str($query_fields, $qf_array);
			$query = array_merge($query, $qf_array);
		} else if (is_array($query_fields)) {
			foreach ($query_fields as $key => $value) {
				if (isset($value)) {
					$query[$key] = $value;
				} else {
					unset($query[$key]);
				}
			}
		}
		if (!empty($query)) {
			return self::$url.'?'.http_build_query($query);
		}
		return self::$url;
	}
	
	static function uri()
	{
		if (!isset(self::$uri)) {
			self::$uri = Pie_Uri::from(self::url());
		}
		return self::$uri;
	}
	
	static function tail(
	 $url = null)
	{
		if (!isset($url)) {
			$url = self::url();
		}
		$base_url = self::baseUrl(true); // first, try with the controller URL
		$base_url_len = strlen($base_url);
		if (substr($url, 0, $base_url_len) != $base_url) {
			$base_url = self::$app_root_url; // okay, try with the app root
			$base_url_len = strlen($base_url);
			if (substr($url, 0, $base_url_len) != $base_url) {
				throw new Pie_Exception_InvalidInput(array('source' => '$url'));
			}
		}
		return substr($url, $base_url_len + 1);
	}
	
	static function filename(
	 $url = null)
	{
		// TODO
	}
	
	/**
	 * Used to forward internally to a new URL
	 */
	static function forward(
	 $to_url)
	{
		// TODO
	}
	
	/**
	 * The names of slots that were requested, if any
	 * @return array
	 */
	static function slotNames()
	{
		$query_param = Pie_Config::get('pie', 'query_param', '_pie');
		if (!isset($_REQUEST[$query_param]['slotNames'])) {
			return null;
		}
		$slotNames = $_REQUEST[$query_param]['slotNames'];
		if (empty($slotNames)) {
			return array();
		}
		if (is_string($slotNames)) {
			$arr = array();
			foreach (explode(',', $slotNames) as $sn) {
				$arr[$sn] = '';
			}
			$slotNames = $arr;
		}
		return $slotNames;
	}
	
	/**
	 * The name of the callback that was specified, if any
	 * @return string
	 */
	static function callback()
	{
		$query_param = Pie_Config::get('pie', 'query_param', '_pie');
		if (empty($_REQUEST[$query_param]['callback'])) {
			return null;
		}
		return $_REQUEST[$query_param]['callback'];
	}
	
	/**
	 * contentToEcho
	 * @return array
	 */
	static function contentToEcho()
	{
		$query_param = Pie_Config::get('pie', 'query_param', '_pie');
		if (!isset($_REQUEST[$query_param]['echo'])) {
			return null;
		}
		return $_REQUEST[$query_param]['echo'];
	}
	
	/**
	 * Use this to determine whether or not it the request is an "AJAX"
	 * request, and is not expecting a full document layout.
	 * @return string
	 *  The contents of _pie['ajax'] if it is present.
	 */
	static function isAjax()
	{
		static $result;
		if (!isset($result)) {
			$result = Pie::event('pie/request/isAjax', array(), 'before');
		}
		if (!isset($result)) {
			$query_param = Pie_Config::get('pie', 'queryField', '_pie');
			$result = isset($_REQUEST[$query_param]['ajax']);
		}
		return $result;
	}
	
	/**
	 * Use this to determine whether or not the request is to be treated
	 * as a POST request by our application.
	 * @return boolean
	 *  Returns true if the request should be treated as a POST.
	 */
	static function isPost()
	{
		static $result;
		if (!isset($result)) {
			$result = Pie::event('pie/request/isPost', array(), 'before');
		}
		if (!isset($result)) {
			$query_param = Pie_Config::get('pie', 'queryField', '_pie');
			if (isset($_REQUEST[$query_param]['post'])) {
				return true;
			}
		}
		return !empty($_POST);
	}
	
	static function accepts($mime_type)
	{
		$ret = Pie::event('pie/request/accepts', compact('mime_type'), 'before');
		if (isset($ret)) {
			return $ret;
		}
		$mt_parts = explode('/', $mime_type);
		
		// Build in support for common scenarios:
		if (isset($_POST['fb_sig_in_canvas'])
		or (isset($_POST['fb_sig_is_ajax']))
		or (isset($_POST['fb_sig_in_profile_tab']))) {
			if ($mime_type === 'text/fbml') {
				return true;
			}
			return false;
		}
		
		// The rest of the time, check the ACCEPT header:
		$accept = array();
		if (!isset($_SERVER['HTTP_ACCEPT'])) {
			$accept = array();
		} else {
			foreach (explode(',', $_SERVER['HTTP_ACCEPT']) as $header){
				$parts = explode(';', $header);
				if (count($parts) === 1) { $parts[1] = true; }
				$accept[$parts[0]] = $parts[1];
			}
		}
		foreach ($accept as $a => $q) {
			$a_parts = explode('/', $a);
			if ($a_parts[0] == $mt_parts[0] or $mt_parts[0] == '*') {
				if (!isset($a_parts[1]) or $a_parts[1] == $mt_parts[1]) {
					return $q;
				}
			}
		}
		return false;
	}
	
	/**
	 * Infers the base URL, with possible controller
	 */
	protected static function inferControllerUrl()
	{
		// Must be called from the web
		if (!isset(self::$url) and !isset($_SERVER['SCRIPT_NAME'])) {
			throw new Exception('$_SERVER["SCRIPT_NAME"] is missing.');
		}

		// Try to infer the web url as follows:
		$script_name = $_SERVER['SCRIPT_NAME'];
		if ($script_name == '' or $script_name == '/')
			$script_name = '/index.php';
		$sub_url = @substr($script_name, 0, strrpos($script_name, '.php'));
		if (empty($sub_url)) {
			// Rewrite rules were used, at the local URL root
			$script_name = '/index.php' . $script_name;
			$sub_url = '/index.php';
		}
		return sprintf('http%s://%s%s%s%s%s%s', 
			isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == TRUE ? 's' : '',
			isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '',
			isset($_SERVER['PHP_AUTH_PW']) ? ':'.$_SERVER['PHP_AUTH_PW'] : '',
			isset($_SERVER['PHP_AUTH_USER']) ? '@' : '', 
			$_SERVER['SERVER_NAME'],
			$_SERVER['SERVER_PORT'] != (isset($_SERVER['HTTPS']) ? 443 : 80) 
				? ':'.$_SERVER['SERVER_PORT'] : '',
			$script_name);
	}
	
	/**
	 * Gets the app root url
	 */
	protected static function getAppRootUrl()
	{
		if (isset(self::$app_root_url)) {
			return self::$app_root_url;
		}
		$app_root = substr($_SERVER['SCRIPT_NAME'], 
		 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
		return sprintf('http%s://%s%s%s%s%s%s', 
			isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == TRUE ? 's' : '',
			isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '',
			isset($_SERVER['PHP_AUTH_PW']) ? ':'.$_SERVER['PHP_AUTH_PW'] : '',
			isset($_SERVER['PHP_AUTH_USER']) ? '@' : '',
			$_SERVER['SERVER_NAME'], 
			$_SERVER['SERVER_PORT'] != (isset($_SERVER['HTTPS']) ? 443 : 80) 
				? ':'.$_SERVER['SERVER_PORT'] : '',
			$app_root);
	}
	
	static protected $url = null;
	static protected $uri = null;
	static protected $controller_url = null;
	static protected $base_url = null;
	static protected $app_root_url = null;
	static protected $controller_present = null;
}
