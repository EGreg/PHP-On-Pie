<?php

/**
 * Override pie/noModule handler.
 * just goes on to render our app's response,
 * which will echo a 404 view.
 */
function pie_noModule($params)
{
	if (!Pie_Request::accepts('text/fbml')) {
		header("HTTP/1.0 404 Not Found");
	}
	Pie_Dispatcher::uri()->module = Pie_Config::expect('pie', 'app');
	Pie_Dispatcher::uri()->action = '';
	Pie::event('pie/response', $params);
}
