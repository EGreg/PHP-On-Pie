<?php

/**
 * Override pie/notFound handler.
 * just goes on to render our app's response,
 * which will echo a 404 view.
 */
function pie_notFound($params)
{
	if (!Pie_Dispatcher::uri()->facebook) {
		header("HTTP/1.0 404 Not Found");
	}
	Pie_Dispatcher::uri()->module = Pie_Config::expect('pie', 'app');
	Pie_Dispatcher::uri()->action = 'notFound';
	Pie::event('pie/response', $params);
}
