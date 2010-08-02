<?php

/**
 * Default pie/noModule handler.
 * Just displays pie/notFound.php view.
 */
function pie_noModule($params)
{
	header("HTTP/1.0 404 Not Found");
	$url = Pie_Request::url();
	echo Pie::view('pie/notFound.php', compact('url'));
}
