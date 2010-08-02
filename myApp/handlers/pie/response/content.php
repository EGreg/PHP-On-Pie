<?php

function pie_response_content()
{	
	$serve_fbml = Pie_Request::accepts('text/fbml');
	
	if ($serve_fbml) {
		// add more fbjs files here
	} else {
		// the js files for your app
		Pie_Response::addScript('plugins/pie/js/Pie.js');
		Pie_Response::addScript("http://cdn.jquerytools.org/1.2.3/jquery.tools.min.js");
		Pie_Response::addScript('plugins/users/js/Users.js');
		// See views/layout/html.php for a facebook script at the top of the <body>
	}
	
	Pie_Response::addStylesheet('plugins/pie/css/Ui.css');
	
	$app = Pie_Config::expect('pie', 'app');
	$url = Pie_Request::url();
	$module = Pie_Dispatcher::uri()->module;
	if (empty($module)) {
		return Pie::event("$app/notFound/response/content");
	}
	$action = Pie_Dispatcher::uri()->action;
	$event = "$module/$action/response/content";
	if (!Pie::canHandle($event)) {
		return Pie::event("$app/notFound/response/content");
	}
		
	// Go ahead and fire the event, returning the result.
	return Pie::event($event);
	
}
