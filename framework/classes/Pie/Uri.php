<?php

/**
 * Represents an internal URI
 * @package Pie
 * @property string $module
 * @property string $action
 *
 */
class Pie_Uri
{
	/**
	 * This can't be created from
	 */
	protected function __construct()
	{
		
	}
	
	/**
	 * Constructs a URI object from something
	 * @param string $source
	 *  An absolute URL, or an array, or a URI in string form.
	 * @param string $route_pattern
	 *  The pattern of the route in the routes config
	 *  If not specified, then Pie searches all the route patterns
	 *  in order, until it finds one that fits.
	 * @return Pie_Uri|false
	 *  Returns false if no route patterns match.
	 *  Otherwise, returns the URI.
	 */
	static function from(
	 $source,
	 $route_pattern = null)
	{
		if (empty($source)) {
			return null;
		}
			
		if (is_array($source)) {
			return self::fromArray($source);
		}
		
		if (is_string($source)) {
			if (Pie_Valid::url($source)) {
				return self::fromUrl($source, $route_pattern);
			} else {
				return self::fromString($source);
			}
		}
		
		if ($source instanceof Pie_Uri) {
			$source2 = clone $source;
			return $source2;
		}
	}
	
	function __toString()
	{
		// returns it as a string
		$module = isset($this->fields['module']) ? $this->fields['module'] : null;
		$action = isset($this->fields['action']) ? $this->fields['action'] : null;
		$result = "$module/$action";
		$other_fields = array();
		foreach ($this->fields as $name => $value) {
			if (is_numeric($name) 
			 or $name == 'action' 
			 or $name == 'module') {
				continue;
			}
			$other_fields[$name] = $value;
		}
		if (!empty($other_fields)) {
			$result .= " " . self::encode($other_fields);
		}
		return $result;
	}
	
	function toArray()
	{
		// returns it as an array
		return $this->fields;
	}
	
	static function url(
	 $source,
	 $route_pattern = null,
	 $no_proxy = false,
	 $controller = true)
	{
		if ($source === '') {
			return '';
		}
		
		if (Pie_Valid::url($source)) {
			// $source is already a URL
			return self::proxySource($source);
		}
		
		if (is_string($source) and isset($source[0]) and $source[0] == '#') {
			// $source is a fragment reference
			return $source;
		}
		
		$url = Pie::event('pie/url', compact('source', 'route_pattern', 'no_proxy'), 'before');
		if (!isset($url)) {
			$uri = self::from($source);
			if (!$uri) {
				$url = null;
			} else { 
				if ($controller === true) {
					// If developer set a custom controller, calculate it.
					$cs = Pie_Config::get('pie', 'web', 'controllerSuffix', null);
					if (isset($cs)) {
						$controller = $cs;
					}
				}
				$url = $uri->toUrl($route_pattern, $controller);
			}
		}
		if (!isset($url)) {
			$hash = Pie_Config::get('pie', 'uri', 'unreachableUri', '#_noRouteToUri');
			if ($hash) {
				return $hash;
			}
		}
		if ($no_proxy) {
			return $url;
		}
		return self::proxySource($url);
	}
	
	/**
	 * Set a suffix for all URLs that will be generated with this class.
	 * @param array $suffix
	 *  Optional. If no arguments are passed, just returns the current suffix.
	 *  Pass an array here. For each entry, the key is tested and if it
	 *  begins the URL, then the value is appended.
	 *  Suffixes are applied when URLs are generated.
	 * @return array
	 *  Returns the suffix at the time the function was called.
	 */
	static function suffix($suffix = null)
	{
		if (is_string($suffix)) {
			$suffix = array('' => $suffix);
		}
		if (!isset($suffix)) {
			return isset(self::$suffix) ? self::$suffix : array();
		}
		$prev_suffix = self::$suffix;
		self::$suffix = $suffix;
		return $prev_suffix;
	}
	
	/**
	 * Returns the value of the specified URI field, or null
	 * if it is not present.
	 * @param string $field_name
	 *  The name of the field.
	 * @return string|null
	 *  Returns the value of the field, or null if not there.
	 */
	function __get($field_name)
	{
		if (isset($this->fields[$field_name])) {
			return $this->fields[$field_name];
		}
		return null;
	}
	
	/**
	 * Sets the value of the specified URI field
	 * @param string $field_name
	 *  The name of the field.
	 * @param string $value
	 *  The value will be converted to a string.
	 */
	function __set($field_name, $value)
	{
		$this->fields[$field_name] = (string)$value;
	}
	
	/**
	 * Returns whether the specified URI field is set
	 * @param string $field_name
	 *  The name of the field.
	 */
	function __isset($field_name)
	{
		return isset($this->fields[$field_name]);
	}
	
	//
	// Internal
	//
	
	protected static function fromUrl(
	 $url,
	 $route_pattern = null)
	{
		if (empty($url))
			return null;
			
		static $routed_cache = array();
		if (isset($routed_cache[$url])) {
			return $routed_cache[$url];
		}
		
		$uri = Pie::event('pie/uriFromUrl', compact('url'), 'before');
		if (isset($uri)) {
			$routed_cache[$url] = $uri;
			return $uri;
		}
			
		$routes = Pie_Config::get('pie', 'routes', array());
		if (empty($routes)) {
			return self::fromArray(array(
				'module' => 'pie', 
				'action' => 'welcome'
			));
		}
		$base_url = Pie_Request::baseUrl(true);

		$len = strlen($base_url);
		$head = substr($url, 0, $len);
		if ($head != $base_url) {
			// try applying proxies before giving up
			$dest_url = self::proxyDestination($url);
			if ($head != $base_url) {
				// even the proxy destination doesn't match.
				throw new Pie_Exception_BadUrl(compact('base_url', 'url'));
			}
			$result = self::fromUrl($dest_url, $route_pattern);
			if (!empty($result)) {
				return $result;
			} else {
		    	throw new Pie_Exception_BadUrl(compact('base_url', 'url'));
			}
		}
		
		// Get the path within our app
		$tail = Pie_Request::tail();
		$p = explode('#', $tail);
		$p2 = explode('?', $p[0]);
		$path = $p2[0];

		// Break it up into segments and try the routes
		$segments = $path ? explode('/', $path) : array();
		$uri_fields = null;

		if ($route_pattern) {
			if (! array_key_exists($route_pattern, $routes))
				throw new Pie_Exception_MissingRoute(compact('route_pattern'));
			$uri_fields = self::matchSegments($route_pattern, $segments);
		} else {
			foreach ($routes as $pattern => $fields) {
				if (!isset($fields))
					continue; // this provides a way to disable a route via config
				$uri_fields = self::matchSegments($pattern, $segments);
				if ($uri_fields !== false) {
					// If we are here, then the route has matched!
					$route_pattern = $pattern;
					break;
				}
			}
		}

		if (!is_array($uri_fields)) {
			// No route has matched
			return self::fromArray(array());
		}
		
		// Now, fill in any extra fields, if present
		if (is_array($routes[$route_pattern])) {
			$uri_fields = array_merge($uri_fields, $routes[$route_pattern]);
		}

		$uri = self::fromArray($uri_fields);
		if (isset($route_pattern)) {
			$uri->route_pattern = $route_pattern;
		}
		$routed_cache[$url] = $uri;
		return $uri;
	}

	/**
	 * Maps this URI into an external URL.
	 *
	 * @param string $rule_name 
	 *  Optional. If you name the route to use for unrouting,
	 *  it wil be used as much as possible.
	 *  Otherwise, PIE will go through the routes one by one in order,
	 *  until it finds one that can route a URL to the full URI
	 *  contained in this object.
	 * @param string $controller
	 *  Optional. You can supply a different controller name, like 'tool.php'
	 * @return string 
	 *  If a $route_pattern is specified, the router uses this route 
	 *  and replaces as many variables as it can to match the $internal_destination. 
	 *  If not, the router tries to find a route and use it to 
	 *  make an external URL that maps to the internal destination
	 *  exactly, but if none of the routes can do this, it returns 
	 *  an empty string.
	 *  You may want to use Uri::proxySource() on the returned url to get
	 *  the proxy url corresponding to it.
	 */
	function toUrl(
	 $route_pattern = null,
	 $controller = true)
	{
		if (empty($this->fields))
			return null;
		
		$routes = Pie_Config::get('pie', 'routes', array());
		if (empty($routes)) {
			return Pie_Request::baseUrl($controller);
		}
		
		$fields = array();
		foreach ($this->fields as $name => $value) {
			if (is_numeric($name))
				continue;
			if ($name == 'action') {
				$value_parts = explode('#', $value, 2);
				$value = $value_parts[0];
				if (count($value_parts) > 1) {
					$anchor = $value_parts[1];
				}
				$value_parts = explode('?', $value, 2);
				$value = $value_parts[0];
				if (count($value_parts) > 1) {
					$querystring = $value_parts[1];
				}
			}
			$fields[$name] = $value;
		}

		if ($route_pattern) {
			$segments = explode('/', $route_pattern);
			$segments2 = array();
			foreach ($segments as $s) {
				if (isset($s[0]) and ($s[0] == '$')) {
					$segments2[] = $fields[substr($s[0], 1)];
				}
			}
			$url = Pie_Request::baseUrl($controller).'/'.implode('/',$segments2);
		} else {
			foreach ($routes as $pattern => $fields) {
				if (!isset($fields))
					continue;
				$url = $this->matchRoute($pattern, $fields, $controller);
				if ($url) {
					$suffix = self::suffix();
					if (is_string($suffix)) {
						$url .= self::suffix();
					} else {
						// aggregate suffixes
						foreach ($suffix as $k => $v) {
							$k_len = strlen($k);
							if (substr($url, 0, $k_len) === $k) {
								$url .= $v;
							}
						}
					}
					return self::fixUrl($url);
				}
			}
		}
				
		return null;
	}
	
	function routePattern()
	{
		return $this->route_pattern;
	}
	
	/**
	 * @param string $pattern
	 *  The pattern (of the rule) to match
	 * @param string $segments
	 *  The segments extracted from the URL
	 * @return array|false
	 *  Returns false if one of the literal values doesn't match up,
	 *  Otherwise, returns array of field => name pairs
	 *  where fields were filled.
	 */
	protected static function matchSegments($pattern, $segments)
	{
		$route_segments = $pattern ? explode('/', $pattern) : array();
		$count = count($route_segments);
		// Length test
		if ($count != count($segments))
			return false; // rule does not match
		// Segments matching test
		$args = array();
		for ($i = 0; $i < $count; ++ $i) {
			$rs = $route_segments[$i];
			$segment = urldecode($segments[$i]);
			if (!isset($rs[0]) or ($rs[0] != '$')) {
				// literal value in segment
				if ($rs != $segment)
					return false;
				continue;
			}
			// otherwise, $variable in segment
			$segment2 = $segment;
			$rs_parts = explode('.', $rs);
			if (count($rs_parts) > 1) {
				// we have $variable.literal in segment
				$segment_parts = explode('.', $segment);
				if (end($rs_parts) != end($segment_parts))
					return false;
				$segment2 = $segment_parts[0];
			}
			// assign variable to args
			$args[substr($rs_parts[0], 1)] = $segment2;
		}
		return $args;
	}
	
	/**
	 * @param string $pattern
	 * @param array $fields
	 * @param string $controller
	 *  
	 * @return string|false
	 *  Returns false if even one field doesn't match.
	 *  Otherwise, returns the URL that would be routed to this uri.
	 */
	protected function matchRoute(
	 $pattern, 
	 $fields,
	 $controller = true)
	{	
		$segments = explode('/', $pattern);
		$segments2 = array();
		$pattern_fields = array();
		foreach ($segments as $s) {
			if (!isset($s[0]) or ($s[0] != '$')) {
				// A literal segment
				$segments2[] = $s;
				continue;
			}
			$k = substr($s, 1);
			$k_parts = explode('.', $k, 2);
			if (count($k_parts) > 1) {
				$k1 = reset($k_parts);
				$k2 = '.'.end($k_parts);
			} else {
				$k1 = $k;
				$k2 = '';
			}
			if (!array_key_exists($k1, $this->fields)) {
				return false;
			}
			$segments2[] = urlencode($this->fields[$k1] . $k2);
			$pattern_fields[$k1] = $this->fields[$k1];
		}

		// Test if all the fields match
		foreach ($fields as $name => $value) {
			if ((!isset($this->fields[$name])) or $this->fields[$name] != $value) {
				return false;
			}
		}

		// Test field matches the other way
		$combined_fields = array_merge($pattern_fields, $fields);
		foreach ($this->fields as $name => $value) {
			if (!isset($combined_fields[$name])
			 or $combined_fields[$name] != $value) {
				return false;
			}
		}

		$url = Pie_Request::baseUrl($controller).'/'.implode('/', $segments2);
		return $url;
	}
	
	protected static function proxyDestination(
	 $url)
	{
		$proxies = Pie_Config::get('pie', 'proxies', array());
		foreach ($proxies as $dest_url => $src_url) {
			$src_url_strlen = strlen($src_url);
			if (substr($url, 0, $src_url_strlen) == $src_url) {
				if (!isset($url[$src_url_strlen]) 
				or $url[$src_url_strlen] == '/') {
					return $dest_url.substr($url, $src_url_strlen);
				}
			}
		}
		return $url;
	}
	
	protected static function proxySource(
	 $url)
	{
		$url = self::fixUrl($url);
		$proxies = Pie_Config::get('pie', 'proxies', array());
		foreach ($proxies as $dest_url => $src_url) {
			$dest_url_strlen = strlen($dest_url);
			if (substr($url, 0, $dest_url_strlen) == $dest_url) {
				if (!isset($url[$dest_url_strlen]) 
				or $url[$dest_url_strlen] == '/') {
					return $src_url.substr($url, $dest_url_strlen);
				}
			}
		}
		return $url;
	}
	
	static function documentRoot()
	{
		$docroot_dir = Pie_Config::get('pie', 'docroot_dir', null);
		if (empty($docroot_dir))
			$docroot_dir = $_SERVER['DOCUMENT_ROOT'];
		$docroot_dir = str_replace("\\", '/', $docroot_dir);
		if (substr($docroot_dir, -1) == '/')
			$docroot_dir = substr($docroot_dir, 0, strlen($docroot_dir) - 1);
		return $docroot_dir;
	}
	
	/**
	 * Returns what the local filename of a local URL would typically be without any routing.
	 * If not found under docroot, also checks various aliases.
	 *
	 * @param string $url
	 *  The url to translate, whether local or an absolute url beginning with the base URL
	 * @return string 
	 *  The complete filename of the file or directory.
	 *  It may not point to an actual file or directory, so use file_exists() or realpath();s
	 */
	static function filenamefromUrl ($url)
	{
		if (Pie_Valid::url($url)) {
			// This is an absolute URL. Get only the part after the base URL
			// Run it through proxies first
			$url = self::proxyDestination($url);
			$local_url = Pie_Request::tail($url);
		} else {
			$local_url = $url;
		}
		$parts = explode('?', $local_url);
		$local_url = $parts[0];

		if ($local_url == '' || $local_url[0] != '/')
			$local_url = '/' . $local_url;

		// Try various aliases first
		$aliases = Pie_Config::get('pie', 'aliases', array());
		foreach ($aliases as $alias => $path) {
			$alias_len = strlen($alias);
			if (substr($local_url, 0, $alias_len) == $alias) {
				return $path . substr($local_url, $alias_len);
			}
		}
		
		// Otherwise, we should use the document root.
		$docroot_dir = self::documentRoot();
		return $docroot_dir.$local_url;
	}
	
	/**
	 * Fixes a URL to have only one question mark
	 * @param string $url
	 *  The url to fix
	 * @return string
	 *  The URL with all subsequent ? replaced by &
	 */
	static function fixUrl($url)
	{
		$pieces = explode('?', $url);
		$url = $pieces[0];
		if (isset($pieces[1]))
			$url .= '?' . implode('&', array_slice($pieces, 1));
		return $url;
	}
	
	protected static function fromArray(
	 $source)
	{
		$u = new Pie_Uri();
		$u->fields = $source;
		return $u;
	}
	
	protected static function fromString(
	 $source)
	{		
		if (!is_string($source)) {
			// Better to throw an exception that return a non-object,
			// which may cause a fatal error
			throw new Pie_Exception_WrongType(array('field' => 'source', 'type' => 'string'));
		}
		$source_parts = explode(" ", $source, 2);
		list($module, $action) = explode('/', $source_parts[0], 2);
		$uri = new Pie_Uri();
		$uri->fields['module'] = $module;
		$uri->fields['action'] = $action;
		if (count($source_parts) > 1) {
			$more_fields = self::decode($source_parts[1]);
			foreach ($more_fields as $name => $value) {
				$uri->fields[$name] = $value;
			}
		}
		return $uri;
	}
	
	/**
	 * @param array $fields
	 * @return string
	 */
	protected static function encode(
	 $fields)
	{
		return json_encode($fields);
	}
	
	/**
	 * Takes a tail full of clauses that look like "a=b c=d"
	 * and turns them into an array.
	 * @param string $tail
	 * @return array
	 */
	protected static function decode(
	 $tail)
	{
		return json_decode($tail, true);
	}
	
	protected $fields = array();
	protected $route_pattern = null;
	protected static $suffix = array();
}
