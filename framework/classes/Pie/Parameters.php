<?php

/**
 * Used to hold arbitrary-dimensional lists of parameters in Pie
 * @package Pie
 */
class Pie_Parameters
{	
	function __construct(&$linked_array = null)
	{
		if (isset($linked_array))
			$this->parameters = &$linked_array;
	}
	
	/**
	 * Gets the array of all parameters
	 */
	function getAll()
	{
		return $this->parameters;
	}
	
	/**
	 * Gets the value of a field, possibly deep inside the array
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
	function get(
	 $key1,
	 $default)
	{
		$args = func_get_args();
		$args_count = func_num_args();
		if ($args_count <= 1)
			return null;
		$default = $args[$args_count - 1];
		$key_array = array();
		$result = & $this->parameters;
		for ($i = 0; $i < $args_count - 1; ++$i) {
			$key = $args[$i];
			if (! is_array($result)) {
				$keys = '["' . implode('"]["', $key_array) . '"]';
				throw new Pie_Exception_NotArray(compact('keys', 'key'));
			}
			if (array_key_exists($key, $result)) {
				if ($i == $args_count - 2) {
					// return the final value
					return $result[$key];
				}
				$result = & $result[$key];
			} else {
				return $default;
			}
			$key_array[] = $key;
		}
	}
	
	/**
	 * Sets the value of a field, possibly deep inside the array
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
	function set(
	 $key1,
	 $value = null)
	{
		$args = func_get_args();
		$args_count = func_num_args();
		if ($args_count <= 1) {
			if (is_array($key1)) {
				foreach ($key1 as $k => $v) {
					$this->parameters[$k] = $v;
				}
			}
			return null;
		}
		$value = $args[$args_count - 1];
		$result = & $this->parameters;
		for ($i = 0; $i < $args_count - 2; ++$i) {
			$key = $args[$i];
			if (! is_array($result)) {
				$result = array(); // overwrite with empty array
			}
			if (isset($key)) {
				$result = & $result[$key];
			} else {
				$result = & $result[];
			}
		}
		
		// set the final value
		$key = $args[$args_count - 2];
		if (isset($key)) {
			$result[$key] = $value;
		} else {
			$result[] = $value;
		}
	}
	
	/**
	 * Clears the value of a field, possibly deep inside the array
	 * @param string $key1
	 *  The name of the first key in the configuration path
	 * @param string $key2
	 *  Optional. The name of the second key in the configuration path.
	 *  You can actually pass as many keys as you need,
	 *  delving deeper and deeper into the configuration structure.
	 *  All but the second-to-last parameter are interpreted as keys.
	 */
	function clear(
	 $key1)
	{
		$args = func_get_args();
		$args_count = func_num_args();
		if ($args_count == 0) {
			$this->parameters = array();
			return;
		}
		$result = & $this->parameters;
		for ($i = 0; $i < $args_count - 1; ++$i) {
			$key = $args[$i];
			if (! is_array($result) 
			 or !array_key_exists($key, $result)) {
				return false;
			}
			$result = & $result[$key];
		}
		// clear the final value
		$key = $args[$args_count - 1];
		if (isset($key)) {
			unset($result[$key]);
		} else {
			array_pop($result);
		}
	}
	
	/**
	 * Loads all the configuration files matching $pattern
	 * @param string $filename
	 *  The filename of the file to load.
	 * @return boolean
	 *  Returns true if saved, otherwise false.
	 */
	function load(
	 $filename)
	{
		$filename2 = Pie::realPath($filename);
		if (!$filename2)
			return false;

		$contents = file_get_contents($filename2);
		$arr = json_decode($contents, true);
		if (!isset($arr)) {
			throw new Pie_Exception_InvalidInput(array('source' => $filename));
		}
		if (is_array($arr)) {
			$this->merge($arr);
		}
		return true;
	}
	
	/**
	 * Saves parameters to a file
	 *
	 * @param string $filename 
	 *  Name of file to save to
	 * @param array $array_path
	 *  Optional. Array of keys identifying the path
	 *  of the config subtree to save
	 * @return bool 
	 *  Returns true if saved, otherwise false;
	 * @author Gregory
	 **/
	function save (
	 $filename, 
	 $array_path = array())
	{
		if (empty($array_path)) {
			$array_path = array();
			$to_save = $this->parameters;
		} else {
			$array_path[] = null;
			$to_save = call_user_func_array(array($this, 'get'), $array_path);
		}
		return file_put_contents($filename, json_encode($to_save));
	}
	
	/**
	 * Merges parameters over the top of existing parameters
	 *
	 * @param array|Pie_Parameters $second
	 *  The array or Pie_Parameters to merge on top of the existing one
	 * @author Gregory
	 **/
	function merge ($second)
	{
		if (is_array($second)) {
			$this->parameters = self::merge_internal($this->parameters, 
				$second);
			return true;
		} else if ($second instanceof Pie_Parameters) {
			$this->parameters = self::merge_internal($this->parameters, 
				$second->parameters);
			return true;
		} else {
			return false;
		}
	}
	
	protected static function merge_internal ($array1 = array(), $array2 = array())
	{
		$result = $array1;
		foreach ($array2 as $key => $value) {
			if (is_int($key)) {
				// numeric key, just insert anyway, might be diff
				// resulting key in the result
				$result[] = $value;
				continue;
			}
			if (array_key_exists($key, $result)) {
				if (is_array($value)) {
					if (is_array($result[$key])) {
						$result[$key] = self::merge_internal($result[$key], $value);
					} else {
						$result[$key] = $value;
					}
				} else {
					$result[$key] = $value;
				}
			} else {
				$result[$key] = $value;
			}
		}
		return $result;
	}
	
	protected $parameters = array();
}
