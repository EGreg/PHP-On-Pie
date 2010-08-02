<?php

/**
 * Functions for doing various things
 * @package Pie
 */

class Pie_Utils
{
	function hmac($algo, $data, $key, $raw_output = false)
	{
	    $algo = strtolower($algo);
	    $pack = 'H'.strlen(call_user_func($algo, 'test'));
	    $size = 64;
	    $opad = str_repeat(chr(0x5C), $size);
	    $ipad = str_repeat(chr(0x36), $size);

	    if (strlen($key) > $size) {
	        $key = str_pad(pack($pack, call_user_func($algo, $key)), $size, chr(0x00));
	    } else {
	        $key = str_pad($key, $size, chr(0x00));
	    }

	    for ($i = 0; $i < strlen($key) - 1; $i++) {
	        $opad[$i] = $opad[$i] ^ $key[$i];
	        $ipad[$i] = $ipad[$i] ^ $key[$i];
	    }

	    $output = call_user_func($algo, $opad.pack($pack, $algo($ipad.$data)));

	    return ($raw_output) ? pack($pack, $output) : $output;
	}

	function unique(
		$len = 7, 
		$source = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789')
	{
		$source_len = strlen($source);
		$result = str_repeat(' ', $len);
		for ($i=0; $i<$len; ++$i) {
			$result[$i] = $source[mt_rand(0, $source_len-1)];
		}
		return $result;
	}
}
