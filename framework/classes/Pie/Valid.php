<?php

/**
 * Functions for validating stuff
 * @package Pie
 */

class Pie_Valid
{
	/**
	 * Says whether the first parameter is an absolute URL or not.
	 * @param string $url
	 *  The string to test.
	 * @param string $check_domain
	 *  Whether to check the domain, too
	 * @return boolean
	 */
	static function url(
	 $url,
	 $check_domain = false,
	 &$fixed_url = null)
	{
		if (!is_string($url))
			return false;
		$url_parts = parse_url($url);
		if (empty($url_parts['scheme']))
			return false;
		if ($check_domain) {
			if (! self::domain($url_parts['host']))
				return false;
		}
		// If we are here, it's a URL
		$pieces = explode('?', $url);
		$fixed_url = $pieces[0];
		if (isset($pieces[1]))
			$fixed_url .= '?' . implode('&', array_slice($pieces, 1));
		return true;
	}
	
	static function domain (
	 $domain, 
	 $try_connecting = false)
	{
		if (ip2long($domain) === false) { 
			// Check if domain is IP. If not, it should be valid domain name
			$domain_array = explode(".", $domain);
			$count = count($domain_array);
			if ($count < 2)
				return false; // Not enough parts to domain
			for ($i = 0; $i < $count - 1; $i ++) {
				if (! preg_match(
					"/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", 
					$domain_array[$i]))
					return false;
			}
			if (! preg_match("/^[A-Za-z]{2,4}$/", $domain_array[$count - 1]))
				return false;
		}
		
		if (! $try_connecting)
			return true;
			
		// checks for if MX records in the DNS
		$mxhosts = array();
		if (! self::getmxrr($domain, $mxhosts)) {
			// no mx records, ok to check domain
			if (! fsockopen($domain, 25, $errno, $errstr, 30))
				return false;
			return true;
		} else {
			// mx records found
			foreach ($mxhosts as $host)
				if (fsockopen($host, 25, $errno, $errstr, 30))
					return true;
			return false;
		}
	}
	
	static function assoc($array)
	{
	    if ( is_array($array) && ! empty($array) ) {
	        for ( $iterator = count($array) - 1; $iterator; $iterator-- ) {
	            if ( ! array_key_exists($iterator, $array) ) { 
					return true; 
				}
	        }
	        return ! array_key_exists(0, $array);
	    }
	    return false;
	}
	
	
	
	
	static function writable ($path)
	{
		// From PHP.NET
		// Checks both files and folders for writability
		//will work in despite of Windows ACLs bug
		//NOTE: use a trailing slash for folders!!!
		//see http://bugs.php.net/bug.php?id=27609
		//see http://bugs.php.net/bug.php?id=30931
		

		if ($path{strlen($path) - 1} == '/') { // recursively return a temporary file path
			return self::writable($path . uniqid(mt_rand()) . '.tmp');
		} else if (dir($path)) {
			return self::writable($path . '/' . uniqid(mt_rand()) . '.tmp');
		}
			
		// check tmp file for read/write capabilities
		$rm = file_exists($path);
		$f = @fopen($path, 'a');
		if ($f === false)
			return false;
		fclose($f);
		if (! $rm)
			unlink($path);
		return true;
	}

	/**
	 * Determines whether a string represents a valid date
	 * @param string $date_string
	 *  The string to test
	 * @return boolean|array
	 *  Returns false if can't be parsed. Otherwise, an associative array.
	 */
	static function date ($date_string)
	{
		$parsed = date_parse($date_string);
		if ($parsed['error_count'] > 0)
			return false;
		return array(
			'year' => $parsed['year'],
			'month' => $parsed['month'],
			'day' => $parsed['day'],
			'hour' => $parsed['hour'],
			'minute' => $parsed['minute'],
			'second' => $parsed['second']
		);
	}

	// FROM http://www.ilovejackdaniels.com/php/email-address-validation/
	static function email ($email, $try_connecting = false)
	{
		// First, we check that there's one @ symbol, and that the lengths are right
		if (! preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) {
			// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
			return false;
		}
		// Split it into sections to make life easier
		$email_array = explode("@", $email, 2);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i ++) {
			if (! preg_match(
				"@^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$@", 
				$local_array[$i])) {
				return false;
			}
		}
		if (! self::domain($email_array[1], $try_connecting))
			return false;
		return true;
	}
	
	private static function getmxrr ($hostname, &$mxhosts)
	{
		$mxhosts = array();
		exec('%SYSTEMDIRECTORY%\\nslookup.exe -q=mx ' . escapeshellarg($hostname), $result_arr);
		foreach ($result_arr as $line) {
			if (preg_match("/.*mail exchanger = (.*)/", $line, $matches))
				$mxhosts[] = $matches[1];
		}
		return (count($mxhosts) > 0);
	}

	// FROM http://www.thewebmasters.net/php/class.Validator.phps
	static function phone ($phone, &$phone_normalized = null)
	{
		if (empty($phone))
			return false;
		
		$num = preg_replace("/([     ]+)/", "", $phone);
		$num = preg_replace("/(\(|\)|\-|\+)/i", "", $num);
		if (! gettype($num) != 'integer') {
			$stripped = preg_replace("/([0-9]+)/i", "", $num);
			if (! empty($stripped))
				return false;
		}
		
		$phone_normalized = $num;
		
		if ((strlen($num)) < 7 or strlen($num) > 14)
			return false;
		
		return true;
	}

	/**
	 * Use this for validating the nonce
	 * @param boolean $throw_if_invalid
	 *  Optional. If true, throws an exception if the nonce is invalid.
	 */
	static function nonce(
	 $throw_if_invalid = false)
	{
		$snf = Pie_Config::get('pie', 'session', 'nonceField', 'nonce');
		if (isset($_SESSION['pie'][$snf])) {
			if (!isset($_REQUEST['_pie'][$snf])
			 or $_SESSION['pie'][$snf] != $_REQUEST['_pie'][$snf]) {
				if (!$throw_if_invalid) {
					return false;
				}
				$message = Pie_Config::get('pie', 'session', 'nonceMessage',
				 	"Your session has expired (nonce mismatch). Perhaps you logged in as a different user?"
				);
				throw new Pie_Exception_FailedValidation(compact('message'));
			}
		}
		return true;
	}
}
