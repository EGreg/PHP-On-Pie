<?php

/**
 * This class lets you do things related to databases and db results
 * @package Db
 */

class Db_Utils
{

	static function sort (array & $dbRows, $field_name)
	{
		if (empty($field_name))
			throw new Exception('Must supply field name to compare by');
		self::$compare_field_name = $field_name;
		usort($dbRows, array('Db_Utils', 'compare_dbRows'
		));
	}

	static private function compare_dbRows ($dbRow1, $dbRow2)
	{
		$compare_field_name = self::$compare_field_name;
		if ($dbRow1->$compare_field_name > $dbRow2->$compare_field_name)
			return 1; else if ($dbRow1->$compare_field_name == $dbRow2->$compare_field_name)
			return 0; else
			return - 1;
	}

	/**
	 * A very useful function for stripping off only the 
	 * parameters you need from an array. 
	 * Use it for passing parameters to functions in a flexible way.
	 *
	 * @param array $from 
	 *  The associative array from which to take the parameters from, 
	 *  consisting of param => value. For example the $_REQUEST array.
	 * @param array $parameters 
	 *  An associative array of paramName => defaultValue pairs.
	 *  If the parameter was not found in the $from array, 
	 *  the default value is used.
	 * @param string $prefix 
	 *  
	 * 
	 *   If nonempty, then parameter names are
	 *   prepended with this prefix before searching in $from is done.
	 *   The prefix is stripped out in the resulting array.
	 *   Typically used for database rows. 
	 *   If $parameters is empty, ALL items in $from with 
	 *   keys starting with $prefix are returned.
	 * 
	 * @return array 
	 * The parameters are stripped off from the $from array, 
	 * according to the above rules, and returned as an array.
	 */
	static function take (array $from, array $parameters, $prefix = '')
	{
		$result = array(
		)
		;
		if (count($parameters) > 0) {
			// There are parameters to strip off. Observe the prefix, too, if any.
			foreach ($parameters as $key => $value) {
				if (array_key_exists($prefix . $key, $from))
					$result[$key] = $from[$prefix . $key]; else {
					$default = $parameters[$key];
					if ($default instanceof Exception)
						throw $default;
					$result[$key] = $default;
				}
			}
		} else if ($prefix > '') {
			// Parameters aren't specified, but a prefix is.
			$prefixlen = strlen($prefix);
			foreach ($from as $key => $value)
				if (strncmp($key, $prefix, $prefixlen) == 0)
					$result[substr($key, $prefixlen)] = $from[$key];
		} else {
			$result = $from;
		}
		return $result;
	}

	/**
	 * Append a message to the log
	 *
	 * @param string $message the message to append
	 * @param integer $level see E_NOTICE in the php manual, etc.
	 * @param bool $timestamp whether to prepend the current timestamp
	 * @param string $ident the ident string to prepend to the message
	 */
	static function log ($message, $level = LOG_NOTICE, $timestamp = true, $ident = 'Db: ')
	{
		static $logOpen = false;
		if (! $logOpen)
			openlog($ident, LOG_NDELAY | LOG_PID | LOG_PERROR, LOG_USER);
		$logOpen = true;
		syslog($level, 
			($timestamp ? date('Y-m-d h:i:s') . ' ' : '') . $message);
	}

	/**
	 * Combines a dirname and a basename, using a slash if needed.
	 * Use this function to build up paths with the correct DIRECTORY_SEPARATOR.
	 * 
	 * @param string $dirname
	 *  The part of the the filename to append to.
	 *  May or may not include a slash at the end.
	 * @param string $basename
	 *  The part of the filename that comes after the slash
	 *  You can continue to pass more tools as the 3rd, 4th etc.
	 *  parameters to this function, and they will all be
	 *  concatenated into one filename.
	 * @return string
	 *  The combined absolute filename. If it does not exist,
	 *  but the filename appended to the current working directory
	 *  exists, then the latter is returned.
	 */
	static function filename (
		$dirname, $basename = null, $basename2 = null)
	{
		$args = func_get_args();
		$pieces = array();
		$count = count($args);
		for ($i = 0; $i < $count - 1; ++ $i) {
			$pieces[] = (substr($args[$i], - 1) == '/' 
			or substr($args[$i], -1) == "\\"
			or substr($args[$i], -1) == DS) 
				? substr($args[$i], 0, - 1) 
				: $args[$i];
		}
		$pieces[] = $args[$count - 1];
		$filename = implode(DS, $pieces);
		if (!file_exists($filename)) {
			// In this case, try the current working directory
			$cwd = getcwd();
			if ($filename[0] != DS and substr($cwd, -1) != DS)
				$filename_try = $cwd . DS . $filename;
			else
				$filename_try = $cwd . $filename;
			$filename_realpath = realpath($filename_try);
			if ($filename_realpath)
				return $filename_realpath;
		}
		return $filename;
	}

	/**
	 * Exports a simple variable into something that looks nice, nothing fancy (for now)
	 * Does not preserve order of array keys.
	 * @param mixed $var
	 *  the variable to export
	 */
	static function var_export (&$var)
	{
		if (is_string($var)) {
			$var_2 = addslashes($var);
			return "'$var_2'";
		} elseif (is_array($var)) {
			$indexed_values_quoted = array(
			)
			;
			$keyed_values_quoted = array(
			)
			;
			foreach ($var as $key => $value) {
				$value = self::var_export($value);
				if (is_string($key))
					$keyed_values_quoted[] = "'" . addslashes($key) . "' => $value"; else
					$indexed_values_quoted[] = $value;
			}
			$parts = array(
			)
			;
			if (! empty($indexed_values_quoted))
				$parts['indexed'] = implode(', ', $indexed_values_quoted);
			if (! empty($keyed_values_quoted))
				$parts['keyed'] = implode(', ', $keyed_values_quoted);
			$exported = 'array(' . implode(", \n", $parts) . ')';
			return $exported;
		} else {
			return var_export($var, true);
		}
	}

	/**
	 * Saves a text file. Need to enable UTF-8 support here.
	 *
	 * @param string $filename 
	 *  The name of the file to save to. 
	 *  Can be relative to this file, or full.
	 * @param string $contents 
	 *  The text string to save
	 * @return int 
	 *  The number of bytes saved, or false if not saved
	 */
	static function saveTextFile ($filename, $contents)
	{
		$dir = dirname($filename);
		if (!file_exists($dir)) {
			mkdir($dir, 0755, true);
		}
		if (!is_dir($dir)) {
			return false;
		}
		// TODO: implement semaphore based on filename to eliminate race conditions
		$result = @file_put_contents($filename, $contents, LOCK_EX); 
		// TODO: use FILE_TEXT for UTF-8 in PHP6
		return $result;
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
	
	static $compare_field_name;
}
