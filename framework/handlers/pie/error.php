<?php

function pie_error($params)	
{
	extract($params);
	while (ob_get_level() > 1) {
		ob_end_flush();
	}
	if (ob_get_level() == 1) {
		$response = ob_get_clean();
	}
	$errstr = preg_replace("/href='(.+)'/", "href='http://php.net/$1'", $errstr);
	$fixTrace = true;
	$exception = new Pie_Exception_PhpError(
		compact('errstr', 'errfile', 'errline', 'fixTrace'), 
		array()
	);
	Pie::event('pie/exception', compact('exception'));
	exit;
}
