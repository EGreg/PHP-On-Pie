<?php

/**
 * Holds the entire Pie configuration
 * @package Pie
 */
class Pie_Config
{
	/**
	 * Loads all the configuration files matching $pattern
	 * @param string $filename
	 *  The filename of the file to load.
	 * @return boolean
	 *  Returns true if saved, otherwise false.
	 */
	static function load(
	 $filename)
	{
		$args = func_get_args();
		if (!isset(self::$parameters)) {
			self::$parameters = new Pie_Parameters;
		}
		return call_user_func_array(array(self::$parameters, __FUNCTION__), $args);
	}
	
	/**
	 * Saves the configuration to a file
	 * @param string $file
	 *  The file to save into
	 * @param array $array_path
	 *  Array of keys identifying the path of the
	 *  config subtree to save
	 * @return boolean
	 *  Returns true if saved, otherwise false.
	 */
	static function save(
	 $filename, 
	 $array_path = array())
	{
		$args = func_get_args();
		if (!isset(self::$parameters)) {
			self::$parameters = new Pie_Parameters;
		}
		return call_user_func_array(array(self::$parameters, __FUNCTION__), $args);
	}
	
	/**
	 * Gets the array of all parameters
	 */
	static function getAll ()
	{
		$args = func_get_args();
		if (!isset(self::$parameters)) {
			self::$parameters = new Pie_Parameters;
		}
		return call_user_func_array(array(self::$parameters, __FUNCTION__), $args);
	}
	
	/**
	 * Gets the value of a configuration field
	 * @param string $key1
	 *  The name of the first key in the configuration path
	 * @param string $key2
	 *  Optional. The name of the second key in the configuration path.
	 *  You can actually pass as many keys as you need,
	 *  delving deeper and deeper into the configuration structure.
	 *  All but the second-to-last parameter are interpreted as keys.
	 * @param mixed $default
	 *  The last parameter should not be omitted,
	 *  and contains the default value to return in case
	 *  the requested configuration field was not indicated.
	 */
	static function get(
	 $key1,
	 $default)
	{
		$args = func_get_args();
		if (!isset(self::$parameters)) {
			self::$parameters = new Pie_Parameters;
		}
		return call_user_func_array(array(self::$parameters, __FUNCTION__), $args);
	}
	
	/**
	 * Sets the value of a configuration field
	 * @param string $key1
	 *  The name of the first key in the configuration path
	 * @param string $key2
	 *  Optional. The name of the second key in the configuration path.
	 *  You can actually pass as many keys as you need,
	 *  delving deeper and deeper into the configuration structure.
	 *  All but the second-to-last parameter are interpreted as keys.
	 * @param mixed $value
	 *  The last parameter should not be omitted,
	 *  and contains the value to set the field to.
	 */
	static function set(
	 $key1,
	 $value)
	{
		$args = func_get_args();
		if (!isset(self::$parameters)) {
			self::$parameters = new Pie_Parameters;
		}
		return call_user_func_array(array(self::$parameters, __FUNCTION__), $args);
	}
	
	/**
	 * Clears the value of a configuration field, possibly deep inside the array
	 * @param string $key1
	 *  The name of the first key in the configuration path
	 * @param string $key2
	 *  Optional. The name of the second key in the configuration path.
	 *  You can actually pass as many keys as you need,
	 *  delving deeper and deeper into the configuration structure.
	 *  All but the second-to-last parameter are interpreted as keys.
	 */
	static function clear(
	 $key1)
	{
		$args = func_get_args();
		if (!isset(self::$parameters)) {
			self::$parameters = new Pie_Parameters;
		}
		return call_user_func_array(array(self::$parameters, __FUNCTION__), $args);
	}
	
	/**
	 * Merges parameters over the top of existing parameters
	 *
	 * @param array|Pie_Parameters $second
	 *  The array or Pie_Parameters to merge on top of the existing one
	 * @author Gregory
	 **/
	static function merge ($second)
	{
		$args = func_get_args();
		if (!isset(self::$parameters)) {
			self::$parameters = new Pie_Parameters;
		}
		return call_user_func_array(array(self::$parameters, __FUNCTION__), $args);
	}
	
	/**
	 * Gets the value of a configuration field. If it is null or not set,
	 * throws an exception. Otherwise, it is guaranteed to return a non-null value.
	 * @param string $key1
	 *  The name of the first key in the configuration path
	 * @param string $key2
	 *  Optional. The name of the second key in the configuration path.
	 *  You can actually pass as many keys as you need,
	 *  delving deeper and deeper into the configuration structure.
	 *  All but the second-to-last parameter are interpreted as keys.
	 * @return mixed
	 *  Only returns non-null values
	 * @throws Pie_Exception_MissingConfig
	 *  May throw an exception if the config field is missing.
	 */
	static function expect(
		$key1)
	{
		$args = func_get_args();
		$args2 = array_merge($args, array(null));
		$result = call_user_func_array(array(__CLASS__, 'get'), $args2);
		if (!isset($result)) {
			throw new Pie_Exception_MissingConfig(array(
				'fieldpath' => '"' . implode('"/"', $args) . '"'
			));
		}
		return $result;
	}
	
	static protected $parameters;
}
