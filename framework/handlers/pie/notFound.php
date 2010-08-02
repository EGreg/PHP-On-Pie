<?php

/**
 * Default pie/notFound handler.
 * Just displays pie/notFound.php view.
 */
function pie_notFound($params)
{
	header("HTTP/1.0 404 Not Found");
	Pie_Dispatcher::result("Nothing found");
	$url = Pie_Request::url();
	echo Pie::view('pie/notFound.php', compact('url'));
}
