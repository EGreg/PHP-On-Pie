<?php

/**
 * Main PIE class
 * Contains core PIE functionality.
 *
 * @package Pie
 */

class Pie
{
	/**
	 * Used for shorthand for avoiding when you don't want to write
	 * (isset($some_long_expression) ? $some_long_expression: null)
	 * when you want to avoid possible "undefined variable" errors.
	 * @param reference ref
	 *  The reference to test. Only lvalues can be passed.
	 * @param mixed def
	 *  The default, if the reference isn't set 
	 */
	static function ifset(& $ref, $def = null)
	{
	        return isset($ref) ? $ref : $def;
	}
	
	/**
	 * Returns the number of microseconds since the 
	 * first call to this function (i.e. since script started).
	 *
	 * @return float 
	 *  The number of microseconds, with fractional part
	 * @author Gregory
	 **/
	static function microtime ()
	{
		list ($usec, $sec) = explode(' ', microtime());
		$result = ((float) $usec + (float) $sec) * 1000;
		
		static $microtime_start;
		if (empty($microtime_start)) {
			$microtime_start = $result;
		}
		return $result - $microtime_start;
	}
	
	/**
	 * Default exception handler for Pie
	 * @param Exception $exception
	 **/
	static function exceptionHandler (
	 $exception)
	{
		self::event('pie/exception', compact('exception'));
	}
	
	static function errorHandler (
		$errno,
		$errstr,
		$errfile,
		$errline,
		$errcontext)
	{
		switch ($errno) {
			case E_USER_NOTICE:
				Pie::log($errstr);
				break;
			default:
				self::event('pie/error', compact(
					'errno','errstr','errfile','errline','errcontext'
				));
				break;
		}
	}
	
	/**
	 * Goes through the params and replaces any references
	 * to their names in the string with their value.
	 * References are expected to be of the form $varname.
	 * Thus, there is no need to escape dollar signs
	 * but "\$foo" will be replaced with "\value_of_foo".
	 * @param string $expression
	 *  The string to expand.
	 * @param array $params
	 *  An array of parameters to the expression.
	 *  Variable names in the expression can refer to them.
	 * @return mixed
	 *  The result of the expression
	 */
	static function expandString(
		$expression,
		$params = array())
	{
		foreach ($params as $key => $value) {
			$expression = str_replace('$'.$key, $value, $expression);
		}
		return $expression;
	}
	
	/**
	 * Evaluates a string containing an expression, 
	 * with possible references to parameters.
	 * CAUTION: make sure the expression is safe!!
	 * @param string $expression
	 *  The code to eval.
	 * @param array $params
	 *  Optional. An array of parameters to the expression.
	 *  Variable names in the expression can refer to them.
	 * @return mixed
	 *  The result of the expression
	 */
	static function evalExpression(
	 $expression,
	 $params = array())
	{
		if (is_array($params)) {
			extract($params);
		}
		eval('$value = ' . $expression . ';');
		return $value;
	}
	
	/**
	 * Use for surrounding text, so it can later be processed throughout.
	 */
	static function t($text)
	{
		$text = Pie::event('pie/t', array(), 'before', false, $text);
		return $text;
	}
	
	/**
	 * Check if a file exists in the include path
	 * And if it does, return the absolute path.
	 * @param string $filename
	 *  Name of the file to look for
	 * @param boolean $ignore_cache
	 *  Defaults to false. If true, then this function ignores
	 *  the cached value, if any, and always attempts to search
	 *  for the file. It will cache the new value.
	 * @return string|false
	 *  The absolute path if file exists, false if it does not
	 */
	static function realPath (
		$filename,
		$ignore_cache = false)
	{
		if (!$ignore_cache) {
			// Try the extended cache mechanism, if any
			$result = Pie::event('pie/realPath', array(), 'before');
			if (isset($result)) {
				return $result;
			}
			// Try the native cache mechanism
			if (isset(self::$realPath_results[$filename])) {
				return self::$realPath_results[$filename];
			}
		}
		
		// Do a search for the file
	    $paths = explode(PS, get_include_path());
		array_unshift($paths, "");
		$result = false;
	    foreach ($paths as $path) {
			if (substr($path, -1) == DS) {
	        	$fullpath = $path.$filename;
			} else {
				$fullpath = $path.DS.$filename;
			}
			// Note: the following call to the OS may take some time:
	        if (file_exists($fullpath)) {
	            $result = $fullpath;
				break;
	        }
	    }
	
		// Notify the cache mechanism, if any
		self::$realPath_results[$filename] = $result;
		Pie::event('pie/realPath', compact('result'), 'after');

	    return $result;

	}

	
	/**
	 * Includes a file and evaluates code from it
	 * @param string $filename
	 *  The filename to include
	 * @param array $params
	 *  Optional. Extracts this array before including the file.
	 * @param boolean $once
	 *  Optional. Whether to use include_once instead of include.
	 * @param boolean $get_vars
	 *  Optional. Set to true to return result of get_defined_vars()
	 *  at the end.
	 * @return mixed
	 *  Optional. If true, returns the result of get_defined_vars() at the end.
	 *  Otherwise, returns whatever the file returned.
	 * @throws Pie_Exception_MissingFile
	 *  May throw a Pie_Exception_MissingFile exception.
	 */
	static function includeFile(
	 $filename,
	 array $params = array(),
	 $once = false,
	 $get_vars = false)
	{
		// The event below skips includes to prevent recursion
		$result = self::event(
			'pie/includeFile', 
			compact('filename', 'params', 'once', 'get_vars'), 
			'before', 
			true
		);
		if (isset($result)) {
			// return this result instead
			return $result;
		}

		$abs_filename = self::realPath($filename);
		
		if (!$abs_filename) {
			$include_path = get_include_path();
			require_once(PIE_CLASSES_DIR.DS.'Pie'.DS.'Exception'.DS.'MissingFile.php');
			throw new Pie_Exception_MissingFile(compact('filename', 'include_path'));
		}
		if (is_dir($abs_filename)) {
			$include_path = get_include_path();
			require_once(PIE_CLASSES_DIR.DS.'Pie'.DS.'Exception'.DS.'MissingFile.php');
			throw new Pie_Exception_MissingFile(compact('filename', 'include_path'));
		}

		extract($params);
		if ($get_vars === true) {
			if ($once) {
				if (!isset(self::$included_files[$filename])) {
					self::$included_files[$filename] = true;
					include_once($abs_filename);
				}
			} else {
				include($abs_filename);
			}
			return get_defined_vars();
		} else {
			if ($once) {
				if (!isset(self::$included_files[$filename])) {
					self::$included_files[$filename] = true;
					include_once($abs_filename);
				}
			} else {
				return include($abs_filename);
			}
		}
	}
	
	/**
	 * Default autoloader for Pie
	 * @param string $class_name
	 */
	static function autoload(   
	 $class_name)
	{
		try {
			$filename = self::event('pie/autoload', compact('class_name'), 'before');

			if (!isset($filename)) {
				$class_name_parts = explode('_', $class_name);
				$filename = 'classes'.DS.implode(DS, $class_name_parts).'.php';
			}
			
			// Workaround for Zend Framework, because it has require_once
			// in various places, instead of just relying on autoloading.
			// As a result, we need to add some more directories to the path.
			// The trigger is that we will be loading a file beginning with "classes/Zend".
			// This is handled natively inside this method for the purpose of speed.
			$paths = array('classes/Zend/' => 'classes');
			static $added_paths = array();
			foreach ($paths as $prefix => $new_path) {
				if (substr($filename, 0, strlen($prefix)) != $prefix) {
					continue;
				}
				if (isset($added_paths[$new_path])) {
					break;
				}
				$abs_filename = self::realPath($filename);
				$new_path_parts = array();
				$prev_part = null;
				foreach (explode(DS, $abs_filename) as $part) {
					if ($prev_part == 'classes' and $part == 'Zend') {
						break;
					}
					$prev_part = $part;
					$new_path_parts[] = $part;
				}
				$new_path = implode(DS, $new_path_parts);
		        $paths = array($new_path, get_include_path());
		        set_include_path(implode(PS, $paths));
				$added_paths[$new_path] = true;
			}

			// Now we can include the file
			self::includeFile($filename);
			
			if (!class_exists($class_name)) {
				require_once(PIE_CLASSES_DIR.DS.'Pie'.DS.'Exception'.DS.'MissingClass.php');
				throw new Pie_Exception_MissingClass(compact('class_name'));
			}
						
			self::event('pie/autoload', compact('class_name', 'filename'), 'after');
						
		} catch (Exception $exception) {
			self::event('pie/exception', compact('exception'));
		}
	}
	
	/**
	 * Renders a particular view
	 * @param string $view_name
	 *  The full name of the view
	 * @param array $params
	 *  Parameters to pass to the view
	 * @return string
	 *  The rendered content of the view
	 */
	static function view(
	 $view_name,
	 $params = array())
	{
		require_once(PIE_CLASSES_DIR.DS.'Pie'.DS.'Exception'.DS.'MissingFile.php');
		
		if (empty($params))
			$params = array();
		
		$result = self::event('pie/view', compact('view_name', 'params'), true);
		if (isset($result)) {
			return $result;
		}
		
		$view_name_parts = explode('/', $view_name);
		$filename = 'views'.DS.implode(DS, $view_name_parts);
		try {
			$ob = new Pie_OutputBuffer();
			self::includeFile($filename, $params);
			return $ob->getClean();
		} catch (Pie_Exception_MissingFile $e) {
			$ob->flushHigherBuffers();
			return self::event('pie/missingView', compact('view_name'));
		}
	}
	
	/**
	 * Instantiates a particular tool.
	 * Also generates javascript around it.
	 * @param string $tool_name
	 *  The name of the tool, of the form "$moduleName/$toolName"
	 *  The handler is found in handlers/$moduleName/tool/$toolName
	 * @param array $fields
	 *  The fields passed to the tool
	 * @param array $pie_options
	 *  Options used by Pie when rendering the tool. Can include:
	 *  "id" =>
	 *    an additional ID to distinguish tools instantiated
	 *    side-by-side from each other. Usually numeric.
	 *  "longIdPrefix" => 
	 *    makes the id prefix be the entire class name of the tool
	 *  "script" => 
	 *    if false, then no autogenerated script appears
	 *    if true, then only the autogenerated script appears
	 *    if omitted or null, then both the rendered markup 
	 *    and autogenerated script appear
	 * @return string
	 *  The rendered content of the tool
	 */
	static function tool(
	 $tool_name,
	 $fields = array(),
	 $pie_options = array())
	{
		if (!is_string($tool_name)) {
			throw new Pie_Exception_WrongType(array(
				'field' => '$tool_name', 'type' => 'string'
			));
		}
		$tool_handler = $tool_name.'/tool';
		$returned = Pie::event(
		 'pie/tool/render', 
		 compact('tool_name', 'fields', 'pie_options'),
		 'before'
		);
		if (is_array($returned)) {
			$fields = array_merge($returned, $fields);
		}
		try {
			$result = Pie::event($tool_handler, $fields); // render the tool
		} catch (Pie_Exception_MissingFile $e) {
			$result = self::event('pie/missingTool', compact('tool_name'));
		} catch (Exception $exception) {
			$result = $exception->getMessage();
		}
		// Even if the tool rendering throws an exception,
		// it is important to run the "after" handlers
		Pie::event(
		 'pie/tool/render',
		 compact('tool_name', 'fields', 'pie_options'), 
		 'after', 
		 false, 
		 $result
		);
		return $result;
	}

	/**
	 * Fires a particular event. 
	 * Might result in several handlers being called.
	 * @param string $event_name
	 *  The name of the event
	 * @param array $params
	 *  Parameters to pass to the event
	 * @param boolean $no_handler
	 *  Defaults to false. 
	 *  If true, the handler of the same name is not invoked. 
	 *  Put true here if you just want to fire a pure event, 
	 *  without any default behavior.
	 *  If 'before', only runs the "before" handlers, if any.
	 *  If 'after', only runs the "after" handlers, if any.
	 *  You'd want to signal events with 'before' and 'after'
	 *  before and after some "default behavior" happens.
	 *  Check for a non-null return value on "before",
	 *  and cancel the default behavior if it is present.
	 * @param boolean $skip_includes
	 *  Defaults to false. 
	 *  If true, no new files are loaded. Only handlers which have 
	 *  already been defined as functions are run.
	 * @param reference $result
	 *  Defaults to null. You can pass here a reference to a variable. 
	 *  It will be returned by this function when event handling
	 *  has finished, or has been aborted by an event handler.
	 *  It is passed to all the event handlers, which can modify it.
	 * @return mixed
	 *  Whatever the default event handler returned, or the final
	 *  value of $result if it is modified by any event handlers.
 	 * @throws Pie_Exception_MissingFile
 	 * @throws Pie_Exception_MissingFunction
	 */	
	static function event(
	 $event_name,
	 $params = array(),
	 $no_handler = false,
	 $skip_includes = false,
	 &$result = null)
	{
		// for now, handle only event names which are strings
		if (!is_string($event_name))
			return;
		if (!is_array($params))
			$params = array();
			
		static $event_stack_limit = null;
		if (!isset($event_stack_length_limit)) {
			$event_stack_limit = Pie_Config::get('pie', 'eventStackLimit', 100);
		}
		self::$event_stack[] = compact('event_name', 'params', 'no_handler', 'skip_includes');
		++self::$event_stack_length;		
		if (self::$event_stack_length > $event_stack_limit) {
			if (!class_exists('Pie_Exception_Recursion', false)) {
				include(dirname(__FILE__).DS.'Pie'.DS.'Exception'.DS.'Recursion.php');
			}
			throw new Pie_Exception_Recursion(array('function_name' => "Pie::event($event_name)"));
		}
		
		try {
			if ($no_handler !== 'after') {
				// execute the "before" handlers
				$handlers = Pie_Config::get('pie', 'handlersBeforeEvent', $event_name, array());
				if (is_string($handlers)) {
					$handlers = array($handlers); // be nice
				}
				foreach ($handlers as $handler) {
					if (false === self::handle($handler, $params, $skip_includes, $result)) {
						// return this result instead
						return $result;
					}
				}
			}

			// Execute the primary handler, wherever that is
			if (!$no_handler) {
				// If none of the "after" handlers return anything,
				// the following result will be returned:
				$result = self::handle($event_name, $params, $skip_includes, $result);
			}
			
			if ($no_handler !== 'before') {
				// execute the "after" handlers
				$handlers = Pie_Config::get('pie', 'handlersAfterEvent', $event_name, array());
				if (is_string($handlers)) {
					$handlers = array($handlers); // be nice
				}
				foreach ($handlers as $handler) {
					if (false === self::handle($handler, $params, $skip_includes, $result)) {
						// return this result instead
						return $result;
					}
				}
			}
			array_pop(self::$event_stack);
			--self::$event_stack_length;
		} catch (Exception $e) {
			array_pop(self::$event_stack);
			--self::$event_stack_length;
			throw $e;
		}
			
		// If no handlers ran, the $result is still unchanged.
		return $result;
	}
	
	/**
	 * Tests whether a particular handler exists
	 * @param string $handler_name
	 *  The name of the handler. The handler can be overridden
	 *  via the include path, but an exception is thrown if it is missing.
	 * @param boolean $skip_include
	 *  Defaults to false. If true, no file is loaded;
	 *  the handler is executed only if the function is already defined;
	 *  otherwise, null is returned.
	 * @return boolean
	 *  Whether the handler exists
	 * @throws Pie_Exception_MissingFile
	 * @throws Pie_Exception_MissingFunction
	 */
	static function canHandle(
	 $handler_name,
	 $skip_include = false)
	{
		if (!isset($handler_name)) {
			return false;
		}
		$handler_name_parts = explode('/', $handler_name);
		$function_name = implode('_', $handler_name_parts);
		if (function_exists($function_name))
		 	return true;
		if ($skip_include)
			return false;
		// try to load appropriate file using relative filename
		// (may search multiple include paths)
		$filename = 'handlers'.DS.implode(DS, $handler_name_parts).'.php';
		try {
			self::includeFile($filename);
		} catch (Pie_Exception_MissingFile $e) {
			return false;
		}
		if (function_exists($function_name))
			return true;
		return false;
	}
	
	/**
	 * Executes a particular handler
	 * @param string $handler_name
	 *  The name of the handler. The handler can be overridden
	 *  via the include path, but an exception is thrown if it is missing.
	 * @param array $params
	 *  Parameters to pass to the handler
	 * @param boolean $skip_include
	 *  Defaults to false. If true, no file is loaded;
	 *  the handler is executed only if the function is already defined;
	 *  otherwise, null is returned.
	 * @param reference $result
	 *  Optional. Lets handlers modify return values of events.
	 * @return mixed
	 *  Whatever the particular handler returned, or null otherwise;
	 * @throws Pie_Exception_MissingFile
	 * @throws Pie_Exception_MissingFunction
	 */
	protected static function handle(
	 $handler_name,
	 $params = array(),
	 $skip_include = false,
	 &$result = null)
	{
		if (!isset($handler_name)) {
			return null;
		}
		$handler_name_parts = explode('/', $handler_name);
		$function_name = implode('_', $handler_name_parts);
		if (!is_array($params))
			$params = array();
		if (!function_exists($function_name)) {
			if ($skip_include)
				return null;
			// try to load appropriate file using relative filename
			// (may search multiple include paths)
			$filename = 'handlers'.DS.implode(DS, $handler_name_parts).'.php';
			self::includeFile($filename, $params, true);
			if (!function_exists($function_name)) {
				require_once(PIE_CLASSES_DIR.DS.'Pie'.DS.'Exception'.DS.'MissingFunction.php');
				throw new Pie_Exception_MissingFunction(compact('function_name'));
			}
		}
		// The following avoids the bug in PHP where
		// call_user_func doesn't work with references being passed
		$args = array($params, &$result);
		return call_user_func_array($function_name, $args);
	}
	
	/**
	 * A replacement for call_user_func_array
	 * that implements some conveniences.
	 * @param callable $callback
	 * @param array $params
	 * @return mixed
	 *  Returns whatever the function returned.
	 */
	static function call(
		$callback, 
		$params = array())
	{
		if ($callback === 'echo' or $callback === 'print') {
			foreach ($params as $p) {
				echo $p;
			}
			return;
		}
		$parts = explode('::', $callback);
		if (count($parts) > 1) {
			$callback = array($parts[0], $parts[1]);
		}
		if (!is_callable($callback)) {
			$function_name = $callback;
			if (is_array($function_name)) {
				$function_name = implode('::', $function_name);
			}
			throw new Pie_Exception_MissingFunction(compact('function_name'));
		}
		return call_user_func_array($callback, $params);
	}
	
	/**
	 * Append a message to the main log
	 *
	 * @param mixed $message 
	 *  the message to append. Usually a string.
	 * @param bool $timestamp 
	 *  whether to prepend the current timestamp
	 * @param array $error_log_arguments
	 *  the second, third and fourth arguments to send to error_log
	 */
	static function log (
		$message, 
		$timestamp = true,
		$error_log_arguments = array(0, null, null))
	{		
		if (!is_string($message)) {
			if (!is_object($message)) {
				$message = Pie::var_dump($message, 3, '$', 'text');
			} else {
				if (!is_callable(array($message, '__toString'))) {
					$message = Pie::var_dump($message, null, '$', 'text');
				}
			}
		}
		
		list($type, $destination, $extra_headers) = $error_log_arguments;
		
		$app = Pie_Config::get('pie', 'app', null);
		if (!isset($app)) {
			$app = defined('APP_DIR') ? basename(APP_DIR) : 'Pie App';
		}
		$message = "$app: $message";
		error_log(
			($timestamp ? date('Y-m-d h:i:s') . ' ' : '') 
			. substr($message, 0, ini_get('log_errors_max_len')),
			$type,
			$destination,
			$extra_headers
		);
	}
	
	static function textMode()
	{
		if (!isset($_SERVER['HTTP_HOST'])) {
			return true;
		}
		return false;
	}
	
	/**
	 * Dumps a variable. 
	 * Note: cannot show protected or private members of classes.
	 * @param mixed $var
	 *  the variable to dump
	 * @param integer $max_levels
	 *  the maximum number of levels to recurse
	 * @param string $label
	 *  optional - label of the dumped variable. Defaults to $.
	 * @param boolean $as_text
	 *  if true, dumps as text instead of markup
	 */
	static function var_dump (
		$var, 
		$max_levels = null, 
		$label = '$',
		$return_content = null)
	{
		if ($return_content === 'text') {
			$as_text = true;
		} else {
			$as_text = Pie::textMode();
		}
		
		$scope = false;
		$prefix = 'unique';
		$suffix = 'value';
		
		if ($scope) {
			$vals = $scope;
		} else {
			$vals = $GLOBALS;
		}
		
		$old = $var;
		$var = $new = $prefix . rand() . $suffix;
		$vname = FALSE;
		foreach ($vals as $key => $val)
			if ($val === $new) // ingenious way of finding a global var :)
				$vname = $key;
		$var = $old;
		
		if ($return_content) {
			$ob = new Pie_OutputBuffer();
		}
		if ($as_text) {
			echo PHP_EOL;
		} else {
			echo "<pre style='margin: 0px 0px 10px 0px; display: block; background: white; color: black; font-family: Verdana; border: 1px solid #cccccc; padding: 5px; font-size: 10px; line-height: 13px;'>";
		}
		if (!isset(self::$var_dump_max_levels)) {
			self::$var_dump_max_levels = Pie_Config::get('pie', 'var_dump_max_levels', 5);
		}
		$current_levels = self::$var_dump_max_levels;
		if (isset($max_levels)) {
			self::$var_dump_max_levels = $max_levels;
		}
		self::do_dump($var, $label . $vname, null, null, $as_text);
		if (isset($max_levels)) {
			self::$var_dump_max_levels = $current_levels;
		}
		if ($as_text) {
			echo PHP_EOL;
		} else {
			echo "</pre>";
		}
		
		if ($return_content) {
			return $ob->getClean();
		}
	}

	/**
	 * Exports a simple variable into something that looks nice, nothing fancy (for now)
	 * Does not preserve order of array keys.
	 * @param reference $var
	 *  the variable to export
	 */
	static function var_export (&$var)
	{
		if (is_string($var)) {
			$var_2 = addslashes($var);
			return "'$var_2'";
		} elseif (is_array($var)) {
			$indexed_values_quoted = array();
			$keyed_values_quoted = array();
			foreach ($var as $key => $value) {
				$value = self::var_export($value);
				if (is_string($key)) {
					$keyed_values_quoted[] = "'" . addslashes($key) . "' => $value";
				} else {
					$indexed_values_quoted[] = $value;
				}
			}
			$parts = array();
			if (! empty($indexed_values_quoted))
				$parts['indexed'] = implode(', ', $indexed_values_quoted);
			if (! empty($keyed_values_quoted))
				$parts['keyed'] = implode(', ', $keyed_values_quoted);
			$exported = 'array(' . implode(", ".PHP_EOL, $parts) . ')';
			return $exported;
		} else {
			return var_export($var, true);
		}
	}

	/**
	 * Dumps as a table
	 * @param array $rows
	 */
	static function dump_table ($rows)
	{
		$first_row = true;
		$keys = array();
		$lengths = array();
		foreach ($rows as $row) {
			foreach ($row as $key => $value) {
				if ($first_row) {
					$keys[] = $key;
					$lengths[$key] = strlen($key);
				}
				$val_len = strlen((string)$value);
				if ($val_len > $lengths[$key])
					$lengths[$key] = $val_len;
			}
			$first_row = false;
		}
		foreach ($keys as $i => $key) {
			$key_len = strlen($key);
			if ($key_len < $lengths[$key]) {
				$keys[$i] .= str_repeat(' ', $lengths[$key] - $key_len);
			}
		}
		echo PHP_EOL;
		echo implode("\t", $keys);
		echo PHP_EOL;
		foreach ($rows as $i => $row) {
			foreach ($row as $key => $value) {
				$val_len = strlen((string)$value);
				if ($val_len < $lengths[$key]) {
					$row[$key] .= str_repeat(' ', $lengths[$key] - $val_len);
				}
			}
			echo implode("\t", $row);
			echo PHP_EOL;
		}
	}
	
	/**
	 * Returns stack of events currently being executed.
	 *
	 * @param string $event_name
	 *  Optional. If supplied, searches event stack for this event name.
	 *  If found, returns the latest call with this event name.
	 *  Otherwise, returns false
	 *
	 * @return array|false
	 */
	static function eventStack($event_name = null)
	{
		if (!isset($event_name)) {
			return self::$event_stack;
		}
		foreach (self::$event_stack as $key => $ei) {
			if ($ei['event_name'] === $event_name) {
				return $ei;
			}
		}
		return false;
	}
	
	static function test($pattern)
	{
		if (!is_string($pattern)) {
			return false;
		}
		Pie::var_dump(glob($pattern));
		// TODO: implement
		exit;
	}
	
	static private function do_dump (
		&$var, 
		$var_name = NULL, 
		$indent = NULL, 
		$reference = NULL,
		$as_text = false)
	{
		static $n = null;
		if (!isset($n)) {
			$n = Pie_Config::get('pie', 'newline', "
");
		}
		$do_dump_indent = $as_text
			? "  "
			: "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
		$reference = $reference . $var_name;
		$keyvar = 'the_do_dump_recursion_protection_scheme';
		$keyname = 'referenced_object_name';
		
		$max_indent = self::$var_dump_max_levels;
		if (strlen($indent) >= strlen($do_dump_indent) * $max_indent) {
			echo $indent . $var_name . " (...)$n";
			return;
		}

		if (is_array($var) && isset($var[$keyvar])) {
			$real_var = &$var[$keyvar];
			$real_name = &$var[$keyname];
			$type = ucfirst(gettype($real_var));
			if ($as_text) {
				echo "$indent$var_name<$type> = $real_name$n";
			} else {
				echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
			}
		} else {
			$var = array($keyvar => $var, $keyname => $reference);
			$avar = &$var[$keyvar];
			
			$type = ucfirst(gettype($avar));
			if ($type == "String") {
				$type_color = "green";
			} elseif ($type == "Integer") {
				$type_color = "red";
			} elseif ($type == "Double") {
				$type_color = "#0099c5";
				$type = "Float";
			} elseif ($type == "Boolean") {
				$type_color = "#92008d";
			} elseif ($type == "NULL") {
				$type_color = "black";
			} else {
				$type_color = '#92008d';
			}
			
			if (is_array($avar)) {
				$count = count($avar);
				if ($as_text) {
					echo "$indent" . ($var_name ? "$var_name => " : "") 
						. "<$type>($count)$n$indent($n";
				} else {
					echo "$indent" . ($var_name ? "$var_name => " : "") 
						. "<span style='color:#a2a2a2'>$type ($count)</span><br>$indent(<br>";
				}
				$keys = array_keys($avar);
				foreach ($keys as $name) {
					$value = &$avar[$name];
					$display_name = is_string($name) 
						? "['" . addslashes($name) . "']" 
						: "[$name]";
					self::do_dump($value, $display_name, 
						$indent . $do_dump_indent, $reference, $as_text);
				}
				if ($as_text) {
					echo "$indent)$n";
				} else {
					echo "$indent)<br>";
				}
			} elseif (is_object($avar)) {
				$class = get_class($avar);
				if ($as_text) {
					echo "$indent$var_name<$type>[$class]$n$indent($n";
				} else {
					echo "$indent$var_name <span style='color:$type_color'>$type [$class]</span><br>$indent(<br>";
				}
				if ($avar instanceof Exception) {
					$code = $avar->getCode();
					$message = addslashes($avar->getMessage());
					echo "$indent$do_dump_indent"."code: $code, message: \"$message\"";
					if ($avar instanceof Pie_Exception) {
						echo " inputFields: " . implode(', ', $avar->inputFIelds());
					}
					echo ($as_text ? $n : "<br />");
				}
				
				if (class_exists('Pie_Parameters')
				 and $avar instanceof Pie_Parameters) {
						$getall = $avar->getAll();
						self::do_dump($getall, "", 
						$indent . $do_dump_indent, $reference, $as_text);
				} else if ($avar instanceof Pie_Uri) {
					$arr = $avar->toArray();
					self::do_dump($arr, 'fields', 
						$indent . $do_dump_indent, $reference, $as_text);
					self::do_dump($route_pattern, 'route_pattern', 
						$indent . $do_dump_indent, $reference, $as_text);
				}
					
				if ($avar instanceof Db_Row) {
					foreach ($avar as $name => $value) {
						$modified = $avar->wasModified($name) ? "<span style='color:blue'>*</span>:" : '';
						self::do_dump($value, "$name$modified", 
							$indent . $do_dump_indent, $reference, $as_text);
					}
				} else {
					foreach ($avar as $name => $value) {
						self::do_dump($value, "$name", 
							$indent . $do_dump_indent, $reference, $as_text);
					}
				}
				
				if ($as_text) {
					echo "$indent)$n";
				} else {
					echo "$indent)<br>";
				}
			} elseif (is_int($avar)) {	
				$avar_len = strlen((string)$avar);
				if ($as_text) {
					echo sprintf("$indent$var_name = <$type(%d)>$avar$n", $avar_len);
				} else {
					echo sprintf(
						"$indent$var_name = <span style='color:#a2a2a2'>$type(%d)</span>"
						. " <span style='color:$type_color'>$avar</span><br>", 
						$avar_len
					);
				}
			} elseif (is_string($avar)) {		
				$avar_len = strlen($avar);
				if ($as_text) {
					echo sprintf("$indent$var_name = <$type(%d)> ", $avar_len),
						$avar, "$n";
				} else {
					echo sprintf("$indent$var_name = <span style='color:#a2a2a2'>$type(%d)</span>", 
						$avar_len)
						. " <span style='color:$type_color'>"
						. Pie_Html::text($avar) 
						. "</span><br>";
				}
			} elseif (is_float($avar)) {		
				$avar_len = strlen((string)$avar);
				if ($as_text) {
					echo sprintf("$indent$var_name = <$type(%d)>$avar$n", $avar_len);
				} else {
					echo sprintf(
						"$indent$var_name = <span style='color:#a2a2a2'>$type(%d)</span>"
						. " <span style='color:$type_color'>$avar</span><br>", 
						$avar_len);
				}
			} elseif (is_bool($avar)) {
				$v = ($avar == 1 ? "TRUE" : "FALSE");
				if ($as_text) {
					echo "$indent$var_name = <$type>$v$n";
				} else {
					echo "$indent$var_name = <span style='color:#a2a2a2'>$type</span>"
						. " <span style='color:$type_color'>$v</span><br>";
				}
			} elseif (is_null($avar)) {		
				if ($as_text) {
					echo "$indent$var_name = NULL$n";
				} else {
					echo "$indent$var_name = "
						. " <span style='color:$type_color'>NULL</span><br>";
				}
			} else {		
				$avar_len = strlen((string)$avar);
				if ($as_text) {
					echo sprintf("$indent$var_name = <$type(%d)>$avar$n", $avar_len);
				} else {
					echo sprintf("$indent$var_name = <span style='color:#a2a2a2'>$type(%d)</span>", 
						$avar_len)
						. " <span style='color:$type_color'>"
						. gettype($avar)
						. "</span><br>";
				}
			}
			
			$var = $var[$keyvar];
		}
	}
	
	protected static $included_files = array();
	protected static $var_dump_max_levels;

	protected static $realPath_results = array();
	
	protected static $event_stack = array();
	protected static $event_stack_length = 0;
}

/// { aggregate classes for production
/// Pie/*.php
/// Pie/Exception/MissingFile.php
/// }
